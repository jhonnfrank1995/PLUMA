import { useCallback, useEffect, useState } from 'react';

export interface BorradorRespuesta {
    id: number;
    piezaId: number;
    comentarioId: number;
    periodistaId: number | null;
    borrador: string | null;
    creadaEn: string;
}

export interface TextosComentarios {
    titulo: string;
    cargando: string;
    errorCarga: string;
    errorAccion: string;
    vacio: string;
    borrador: string;
    aprobar: string;
    descartar: string;
}

interface Props {
    restUrl: string;
    nonce: string;
    textos: TextosComentarios;
}

/**
 * Sala de Comentarios (Libro Cap. 5.7, "el editor aprueba con un clic"):
 * bandeja de borradores de respuesta pendientes de aprobación humana —
 * generados por el Orquestador en la voz del periodista, nunca publicados
 * sin que el editor los apruebe primero.
 */
export function PantallaComentarios({ restUrl, nonce, textos }: Props) {
    const [pendientes, setPendientes] = useState<BorradorRespuesta[] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [accionEnCurso, setAccionEnCurso] = useState<number | null>(null);

    const cargar = useCallback(() => {
        fetch(`${restUrl}pluma/v1/comentarios/pendientes`, { headers: { 'X-WP-Nonce': nonce } })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                return respuesta.json() as Promise<BorradorRespuesta[]>;
            })
            .then((json) => {
                setPendientes(json);
                setError(null);
            })
            .catch(() => setError(textos.errorCarga));
    }, [restUrl, nonce, textos.errorCarga]);

    useEffect(() => {
        cargar();
    }, [cargar]);

    const ejecutar = (respuestaId: number, accion: 'aprobar' | 'descartar') => {
        setAccionEnCurso(respuestaId);
        fetch(`${restUrl}pluma/v1/comentarios/${respuestaId}/${accion}`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce },
        })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                cargar();
            })
            .catch(() => setError(textos.errorAccion))
            .finally(() => setAccionEnCurso(null));
    };

    if (null !== error) {
        return (
            <div className="pluma-comentarios pluma-comentarios--error" role="alert">
                {error}
            </div>
        );
    }

    if (null === pendientes) {
        return <div className="pluma-comentarios pluma-comentarios--cargando">{textos.cargando}</div>;
    }

    return (
        <div className="pluma-comentarios">
            <h1>{textos.titulo}</h1>

            {0 === pendientes.length ? (
                <p className="pluma-comentarios__vacio">{textos.vacio}</p>
            ) : (
                <ol className="pluma-comentarios__lista">
                    {pendientes.map((pendiente) => (
                        <li key={pendiente.id} className="pluma-comentarios__tarjeta">
                            <p className="pluma-comentarios__borrador">
                                <span className="pluma-comentarios__etiqueta">{textos.borrador}</span>
                                {pendiente.borrador}
                            </p>

                            <div className="pluma-comentarios__acciones">
                                <button
                                    type="button"
                                    className="pluma-comentarios__boton pluma-comentarios__boton--aprobar"
                                    disabled={accionEnCurso === pendiente.id}
                                    onClick={() => ejecutar(pendiente.id, 'aprobar')}
                                >
                                    {textos.aprobar}
                                </button>
                                <button
                                    type="button"
                                    className="pluma-comentarios__boton pluma-comentarios__boton--descartar"
                                    disabled={accionEnCurso === pendiente.id}
                                    onClick={() => ejecutar(pendiente.id, 'descartar')}
                                >
                                    {textos.descartar}
                                </button>
                            </div>
                        </li>
                    ))}
                </ol>
            )}
        </div>
    );
}
