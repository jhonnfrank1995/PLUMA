import { useState } from 'react';

export interface TextosLlaveOpenRouter {
    titulo: string;
    actual: string;
    campoNueva: string;
    guardar: string;
    probar: string;
    probando: string;
    valida: string;
    invalida: string;
    cambiar: string;
    quitar: string;
    confirmarQuitar: string;
}

interface Props {
    restUrl: string;
    nonce: string;
    configurada: boolean;
    ultimosCuatro: string | null;
    textos: TextosLlaveOpenRouter;
    alGuardar: () => void;
    alError: () => void;
}

/**
 * Bloque de gestión de la llave de OpenRouter (guardar/probar/quitar) —
 * extraído de `PantallaSalaMaquinas` (Etapa 4, porción 6) para reutilizarlo
 * tal cual en el Acto 2 del asistente de instalación (Cap. 10.3), sin
 * duplicar el formulario ni la lógica de guardar/probar/quitar.
 */
export function BloqueLlaveOpenRouter({ restUrl, nonce, configurada, ultimosCuatro, textos, alGuardar, alError }: Props) {
    const [llaveNueva, setLlaveNueva] = useState('');
    const [pruebaLlave, setPruebaLlave] = useState<'sin_probar' | 'probando' | 'valida' | 'invalida'>('sin_probar');
    const [enCurso, setEnCurso] = useState(false);

    const cabeceras = { 'X-WP-Nonce': nonce };

    const probarLlave = () => {
        if ('' === llaveNueva.trim()) {
            return;
        }

        setPruebaLlave('probando');
        fetch(`${restUrl}pluma/v1/motor/llave-openrouter/probar`, {
            method: 'POST',
            headers: { ...cabeceras, 'Content-Type': 'application/json' },
            body: JSON.stringify({ llave: llaveNueva }),
        })
            .then((respuesta) => respuesta.json() as Promise<{ valida: boolean }>)
            .then((json) => setPruebaLlave(json.valida ? 'valida' : 'invalida'))
            .catch(() => setPruebaLlave('invalida'));
    };

    const guardarLlave = () => {
        if ('' === llaveNueva.trim()) {
            return;
        }

        setEnCurso(true);
        fetch(`${restUrl}pluma/v1/motor/llave-openrouter`, {
            method: 'POST',
            headers: { ...cabeceras, 'Content-Type': 'application/json' },
            body: JSON.stringify({ llave: llaveNueva }),
        })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                setLlaveNueva('');
                setPruebaLlave('sin_probar');
                alGuardar();
            })
            .catch(() => alError())
            .finally(() => setEnCurso(false));
    };

    const quitarLlave = () => {
        // eslint-disable-next-line no-alert -- confirmación de una acción real: sin llave, el redactor vuelve al fallback mecánico.
        if (!window.confirm(textos.confirmarQuitar)) {
            return;
        }

        fetch(`${restUrl}pluma/v1/motor/llave-openrouter`, { method: 'DELETE', headers: cabeceras })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                alGuardar();
            })
            .catch(() => alError());
    };

    return (
        <section className="pluma-maquinas__seccion">
            <h2>{textos.titulo}</h2>
            {configurada && (
                <p className="pluma-maquinas__llave-actual">
                    {textos.actual}: sk-…{ultimosCuatro}
                </p>
            )}
            <label className="pluma-maquinas__campo">
                {textos.campoNueva}
                <input
                    type="password"
                    value={llaveNueva}
                    onChange={(evento) => {
                        setLlaveNueva(evento.target.value);
                        setPruebaLlave('sin_probar');
                    }}
                />
            </label>
            <div className="pluma-maquinas__llave-acciones">
                <button type="button" disabled={'' === llaveNueva.trim() || 'probando' === pruebaLlave} onClick={probarLlave}>
                    {'probando' === pruebaLlave ? textos.probando : textos.probar}
                </button>
                <button type="button" disabled={enCurso || '' === llaveNueva.trim()} onClick={guardarLlave}>
                    {configurada ? textos.cambiar : textos.guardar}
                </button>
                {configurada && (
                    <button type="button" className="pluma-maquinas__boton--quitar" onClick={quitarLlave}>
                        {textos.quitar}
                    </button>
                )}
            </div>
            {'valida' === pruebaLlave && <p className="pluma-maquinas__prueba-ok">{textos.valida}</p>}
            {'invalida' === pruebaLlave && (
                <p className="pluma-maquinas__prueba-error" role="alert">
                    {textos.invalida}
                </p>
            )}
        </section>
    );
}
