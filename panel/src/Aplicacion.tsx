import { useEffect, useState } from 'react';
import { BarraEstado } from './BarraEstado';
import { PantallaPortada, type DatosPortada, type TextosPortada } from './PantallaPortada';
import { PantallaSalud, type DatosSalud } from './PantallaSalud';
import { PantallaTendencias, type TextosTendencias } from './PantallaTendencias';

export interface DatosPlumaPanel {
    restUrl: string;
    nonce: string;
    salud: DatosSalud;
    textosPortada: TextosPortada;
    textosTendencias: TextosTendencias;
}

interface Props {
    datos: DatosPlumaPanel;
}

type Ruta = 'portada' | 'tendencias' | 'salud';

const INTERVALO_REFRESCO_MS = 60_000;

function leerRuta(): Ruta {
    if ('#/salud' === window.location.hash) {
        return 'salud';
    }

    return '#/tendencias' === window.location.hash ? 'tendencias' : 'portada';
}

/**
 * El shell del panel (Libro Cap. 10.1): una única página de wp-admin con
 * barra de estado persistente + enrutado por hash entre pantallas — así la
 * barra nunca desaparece al navegar (una recarga completa por pantalla
 * rompería justo la promesa de "saber en tres segundos si todo está bien").
 *
 * Solo se enlazan pantallas que YA EXISTEN (Portada, Sala de Máquinas): cada
 * porción nueva de la Etapa 4 añade su propia entrada de navegación cuando
 * esté lista, nunca antes — cero enlaces muertos.
 */
export function Aplicacion({ datos }: Props) {
    const [ruta, setRuta] = useState<Ruta>(leerRuta);
    const [portada, setPortada] = useState<DatosPortada | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const escuchar = () => setRuta(leerRuta());
        window.addEventListener('hashchange', escuchar);
        return () => window.removeEventListener('hashchange', escuchar);
    }, []);

    useEffect(() => {
        let cancelado = false;

        const cargar = () => {
            fetch(`${datos.restUrl}pluma/v1/panel/portada`, {
                headers: { 'X-WP-Nonce': datos.nonce },
            })
                .then((respuesta) => {
                    if (!respuesta.ok) {
                        throw new Error('respuesta no OK');
                    }
                    return respuesta.json() as Promise<DatosPortada>;
                })
                .then((json) => {
                    if (!cancelado) {
                        setPortada(json);
                        setError(null);
                    }
                })
                .catch(() => {
                    if (!cancelado) {
                        setError(datos.textosPortada.errorCarga);
                    }
                });
        };

        cargar();
        const intervalo = window.setInterval(cargar, INTERVALO_REFRESCO_MS);

        return () => {
            cancelado = true;
            window.clearInterval(intervalo);
        };
    }, [datos.restUrl, datos.nonce, datos.textosPortada.errorCarga]);

    return (
        <div className="pluma-panel">
            <BarraEstado portada={portada} textos={datos.textosPortada} />

            <nav className="pluma-panel__nav" aria-label={datos.textosPortada.titulo}>
                <a href="#/portada" className={'portada' === ruta ? 'pluma-panel__nav-enlace pluma-panel__nav-enlace--activo' : 'pluma-panel__nav-enlace'}>
                    {datos.textosPortada.navPortada}
                </a>
                <a
                    href="#/tendencias"
                    className={'tendencias' === ruta ? 'pluma-panel__nav-enlace pluma-panel__nav-enlace--activo' : 'pluma-panel__nav-enlace'}
                >
                    {datos.textosTendencias.titulo}
                </a>
                <a href="#/salud" className={'salud' === ruta ? 'pluma-panel__nav-enlace pluma-panel__nav-enlace--activo' : 'pluma-panel__nav-enlace'}>
                    {datos.textosPortada.navSalud}
                </a>
            </nav>

            <main className="pluma-panel__contenido">
                {'portada' === ruta && <PantallaPortada datos={portada} error={error} textos={datos.textosPortada} />}
                {'tendencias' === ruta && <PantallaTendencias restUrl={datos.restUrl} nonce={datos.nonce} textos={datos.textosTendencias} />}
                {'salud' === ruta && <PantallaSalud datos={datos.salud} />}
            </main>
        </div>
    );
}
