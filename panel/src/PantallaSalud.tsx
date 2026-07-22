export interface DatosSalud {
    versionPhp: string;
    versionWordPress: string;
    versionBaseDatos: string;
    versionEsquemaPlugin: string;
    cronRealConfigurado: boolean;
    esMultisitio: boolean;
    textos: {
        titulo: string;
        etiquetaPhp: string;
        etiquetaWordPress: string;
        etiquetaBaseDatos: string;
        etiquetaEsquema: string;
        etiquetaCron: string;
        cronOk: string;
        cronAdvertencia: string;
        etiquetaMultisitio: string;
        multisitioSi: string;
        multisitioNo: string;
    };
}

interface Props {
    datos: DatosSalud;
}

/**
 * Sala de Máquinas — Salud del sistema.
 *
 * Primera pantalla real del panel (Libro de Arquitectura, Cap. 10): el editor
 * debe saber en segundos si el hosting está listo para publicación autónoma.
 * Semilla del acto 1 del onboarding (verificación técnica del hosting).
 */
export function PantallaSalud({ datos }: Props) {
    const { textos } = datos;

    return (
        <div className="pluma-salud">
            <h1>{textos.titulo}</h1>
            <dl className="pluma-salud__lista">
                <div className="pluma-salud__fila">
                    <dt>{textos.etiquetaPhp}</dt>
                    <dd>{datos.versionPhp}</dd>
                </div>
                <div className="pluma-salud__fila">
                    <dt>{textos.etiquetaWordPress}</dt>
                    <dd>{datos.versionWordPress}</dd>
                </div>
                <div className="pluma-salud__fila">
                    <dt>{textos.etiquetaBaseDatos}</dt>
                    <dd>{datos.versionBaseDatos}</dd>
                </div>
                <div className="pluma-salud__fila">
                    <dt>{textos.etiquetaEsquema}</dt>
                    <dd>{datos.versionEsquemaPlugin}</dd>
                </div>
                <div className="pluma-salud__fila">
                    <dt>{textos.etiquetaCron}</dt>
                    <dd
                        data-estado={datos.cronRealConfigurado ? 'ok' : 'advertencia'}
                        className={
                            datos.cronRealConfigurado
                                ? 'pluma-salud__estado pluma-salud__estado--ok'
                                : 'pluma-salud__estado pluma-salud__estado--advertencia'
                        }
                    >
                        {datos.cronRealConfigurado ? textos.cronOk : textos.cronAdvertencia}
                    </dd>
                </div>
                <div className="pluma-salud__fila">
                    <dt>{textos.etiquetaMultisitio}</dt>
                    <dd>{datos.esMultisitio ? textos.multisitioSi : textos.multisitioNo}</dd>
                </div>
            </dl>
        </div>
    );
}
