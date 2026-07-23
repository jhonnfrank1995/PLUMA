import { useEffect, useState } from 'react';
import { BarraEstado } from './BarraEstado';
import { PantallaMesaEditorial, type TextosMesaEditorial } from './PantallaMesaEditorial';
import { PantallaPortada, type DatosPortada, type TextosPortada } from './PantallaPortada';
import { PantallaSalud, type DatosSalud } from './PantallaSalud';
import { PantallaTendencias, type TextosTendencias } from './PantallaTendencias';

export interface DatosPlumaPanel {
    restUrl: string;
    nonce: string;
    salud: DatosSalud;
    textosPortada: TextosPortada;
    textosTendencias: TextosTendencias;
    textosMesaEditorial: TextosMesaEditorial;
}

interface Props {
    datos: DatosPlumaPanel;
}

type Ruta = 'portada' | 'tendencias' | 'mesa-editorial' | 'salud';

const INTERVALO_REFRESCO_MS = 60_000;

function leerRuta(): Ruta {
    if ('#/salud' === window.location.hash) {
        return 'salud';
    }

    if ('#/tendencias' === window.location.hash) {
        return 'tendencias';
    }

    return '#/mesa-editorial' === window.location.hash ? 'mesa-editorial' : 'portada';
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
                <a
                    href="#/mesa-editorial"
                    className={'mesa-editorial' === ruta ? 'pluma-panel__nav-enlace pluma-panel__nav-enlace--activo' : 'pluma-panel__nav-enlace'}
                >
                    {datos.textosMesaEditorial.titulo}
                </a>
                <a href="#/salud" className={'salud' === ruta ? 'pluma-panel__nav-enlace pluma-panel__nav-enlace--activo' : 'pluma-panel__nav-enlace'}>
                    {datos.textosPortada.navSalud}
                </a>
            </nav>

            <main className="pluma-panel__contenido">
                {'portada' === ruta && <PantallaPortada datos={portada} error={error} textos={datos.textosPortada} />}
                {'tendencias' === ruta && <PantallaTendencias restUrl={datos.restUrl} nonce={datos.nonce} textos={datos.textosTendencias} />}
                {'mesa-editorial' === ruta && (
                    <PantallaMesaEditorial restUrl={datos.restUrl} nonce={datos.nonce} textos={datos.textosMesaEditorial} />
                )}
                {'salud' === ruta && <PantallaSalud datos={datos.salud} />}
            </main>
        </div>
    );
}
