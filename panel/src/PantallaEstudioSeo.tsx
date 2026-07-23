import { useEffect, useState } from 'react';

export interface GrupoCanibalizacion {
    keywordPrincipal: string;
    piezas: Array<{ piezaId: number; titulo: string; url: string }>;
}

export interface EntradaCuarentena {
    id: number;
    tipo: 'categoria' | 'etiqueta';
    nombre: string;
    vecesUsada: number;
}

export interface PropuestaFusion {
    tipo: 'categoria' | 'etiqueta';
    idA: number;
    nombreA: string;
    idB: number;
    nombreB: string;
    similitud: number;
}

interface DatosVocabulario {
    cuarentena: EntradaCuarentena[];
    propuestasFusion: PropuestaFusion[];
}

export interface TextosEstudioSeo {
    titulo: string;
    cargando: string;
    errorCarga: string;
    canibalizacion: {
        titulo: string;
        vacio: string;
        keyword: string;
        piezas: string;
    };
    taxonomia: {
        titulo: string;
        cuarentenaTitulo: string;
        cuarentenaVacio: string;
        vecesUsada: string;
        fusionTitulo: string;
        fusionVacio: string;
        similitud: string;
    };
    tipo: {
        categoria: string;
        etiqueta: string;
    };
}

interface Props {
    restUrl: string;
    nonce: string;
    textos: TextosEstudioSeo;
}

/**
 * Estudio SEO y Taxonomía (Libro Cap. 10.2): auditoría de canibalización y
 * salud taxonómica, de solo lectura. "Estado de indexación por pieza" y
 * "keywords en el umbral 5-15" quedan fuera — sin fuente real todavía
 * (Search Console, `PLUMA-E3-5`), igual que en la Portada. Las propuestas de
 * fusión se muestran pero no se ejecutan: fusionar de verdad implicaría
 * reasignar términos en posts ya publicados.
 */
export function PantallaEstudioSeo({ restUrl, nonce, textos }: Props) {
    const [canibalizacion, setCanibalizacion] = useState<GrupoCanibalizacion[] | null>(null);
    const [vocabulario, setVocabulario] = useState<DatosVocabulario | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let cancelado = false;
        const cabeceras = { 'X-WP-Nonce': nonce };

        Promise.all([
            fetch(`${restUrl}pluma/v1/seo/canibalizacion`, { headers: cabeceras }).then(
                (r) => r.json() as Promise<GrupoCanibalizacion[]>
            ),
            fetch(`${restUrl}pluma/v1/seo/vocabulario`, { headers: cabeceras }).then((r) => r.json() as Promise<DatosVocabulario>),
        ])
            .then(([listaCanibalizacion, datosVocabulario]) => {
                if (!cancelado) {
                    setCanibalizacion(listaCanibalizacion);
                    setVocabulario(datosVocabulario);
                    setError(null);
                }
            })
            .catch(() => {
                if (!cancelado) {
                    setError(textos.errorCarga);
                }
            });

        return () => {
            cancelado = true;
        };
    }, [restUrl, nonce, textos.errorCarga]);

    if (null !== error) {
        return (
            <p className="pluma-seo__aviso" role="alert">
                {error}
            </p>
        );
    }

    if (null === canibalizacion || null === vocabulario) {
        return <p className="pluma-seo__cargando">{textos.cargando}</p>;
    }

    return (
        <div className="pluma-seo">
            <h1>{textos.titulo}</h1>

            <section className="pluma-seo__seccion">
                <h2>{textos.canibalizacion.titulo}</h2>
                {0 === canibalizacion.length ? (
                    <p className="pluma-seo__vacio">{textos.canibalizacion.vacio}</p>
                ) : (
                    <table className="pluma-seo__tabla">
                        <thead>
                            <tr>
                                <th>{textos.canibalizacion.keyword}</th>
                                <th>{textos.canibalizacion.piezas}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {canibalizacion.map((grupo) => (
                                <tr key={grupo.keywordPrincipal}>
                                    <td>{grupo.keywordPrincipal}</td>
                                    <td>
                                        <ul className="pluma-seo__lista-piezas">
                                            {grupo.piezas.map((pieza) => (
                                                <li key={pieza.piezaId}>
                                                    <a href={pieza.url} target="_blank" rel="noreferrer">
                                                        {pieza.titulo}
                                                    </a>
                                                </li>
                                            ))}
                                        </ul>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </section>

            <section className="pluma-seo__seccion">
                <h2>{textos.taxonomia.titulo}</h2>

                <h3>{textos.taxonomia.cuarentenaTitulo}</h3>
                {0 === vocabulario.cuarentena.length ? (
                    <p className="pluma-seo__vacio">{textos.taxonomia.cuarentenaVacio}</p>
                ) : (
                    <ul className="pluma-seo__lista-cuarentena">
                        {vocabulario.cuarentena.map((entrada) => (
                            <li key={`${entrada.tipo}-${entrada.id}`}>
                                <strong>{entrada.nombre}</strong>
                                <span>({'categoria' === entrada.tipo ? textos.tipo.categoria : textos.tipo.etiqueta})</span>
                                <span>
                                    {textos.taxonomia.vecesUsada}: {entrada.vecesUsada}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}

                <h3>{textos.taxonomia.fusionTitulo}</h3>
                {0 === vocabulario.propuestasFusion.length ? (
                    <p className="pluma-seo__vacio">{textos.taxonomia.fusionVacio}</p>
                ) : (
                    <ul className="pluma-seo__lista-fusion">
                        {vocabulario.propuestasFusion.map((propuesta) => (
                            <li key={`${propuesta.tipo}-${propuesta.idA}-${propuesta.idB}`}>
                                <strong>{propuesta.nombreA}</strong> ↔ <strong>{propuesta.nombreB}</strong>
                                <span>
                                    ({textos.taxonomia.similitud}: {propuesta.similitud.toFixed(1)}%)
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </div>
    );
}
