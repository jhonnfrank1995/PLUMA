import type { DatosPortada, TextosPortada } from './PantallaPortada';

interface Props {
    portada: DatosPortada | null;
    textos: TextosPortada;
}

/**
 * La barra de estado persistente (Libro Cap. 10.1): "el editor debe saber
 * en tres segundos si todo está bien". Visible en TODAS las pantallas del
 * panel, no solo en la Portada — vive en el shell (`Aplicacion.tsx`).
 */
export function BarraEstado({ portada, textos }: Props) {
    if (null === portada) {
        return (
            <header className="pluma-barra-estado" role="banner">
                <span className="pluma-barra-estado__marca">PLUMA Engine</span>
                <span className="pluma-barra-estado__cargando">{textos.cargando}</span>
            </header>
        );
    }

    const totalHoy = portada.cuota.publicadasHoy + portada.cuota.programadasHoy;
    const huboErrores = null !== portada.salud.ultimaEjecucion && portada.salud.ultimaEjecucion.errores.length > 0;

    return (
        <header className="pluma-barra-estado" role="banner">
            <span className="pluma-barra-estado__marca">PLUMA Engine</span>

            <span className={`pluma-barra-estado__modo pluma-barra-estado__modo--${portada.modoOperacion}`}>
                {textos.modo[portada.modoOperacion]}
            </span>

            <span className="pluma-barra-estado__dato">
                {totalHoy}/{portada.cuota.objetivo} {textos.cuota.publicadas}
            </span>

            <span className="pluma-barra-estado__dato">
                {textos.cuota.proximaPublicacion}:{' '}
                {null !== portada.cuota.proximaPublicacion ? formatearHora(portada.cuota.proximaPublicacion) : textos.cuota.sinProximo}
            </span>

            <span
                className={`pluma-barra-estado__salud pluma-barra-estado__salud--${huboErrores ? 'alerta' : 'ok'}`}
                data-estado={huboErrores ? 'alerta' : 'ok'}
            >
                ${portada.salud.gastoHoyUsd.toFixed(2)} {textos.salud.deLimite} ${portada.salud.limiteDiarioUsd.toFixed(2)}
            </span>
        </header>
    );
}

function formatearHora(iso: string): string {
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
