import { useEffect, useState } from 'react';

export interface MetricaSearchConsole {
    postId: number;
    piezaId: number | null;
    consulta: string;
    clics: number;
    impresiones: number;
    ctr: number;
    posicion: number;
    sincronizadaEn: string;
}

interface EstadoSearchConsole {
    conectada: boolean;
    sitioSeleccionado: string | null;
    circuitoAbierto: boolean;
    ultimaSincronizacion: string | null;
    metricasRecientes: MetricaSearchConsole[];
}

interface SitioSearchConsole {
    siteUrl: string;
    permissionLevel: string;
}

export interface TextosSearchConsole {
    titulo: string;
    cargando: string;
    errorCarga: string;
    errorAccion: string;
    avisoConectado: string;
    avisoError: string;
    campoClientId: string;
    campoClientSecret: string;
    conectar: string;
    conectando: string;
    redirectUriTitulo: string;
    redirectUriAyuda: string;
    irAGoogle: string;
    elegirSitio: string;
    guardarSitio: string;
    sitioActual: string;
    sincronizarAhora: string;
    sincronizando: string;
    ultimaSincronizacion: string;
    nuncaSincronizado: string;
    circuitoAbierto: string;
    desconectar: string;
    confirmarDesconectar: string;
    tablaPagina: string;
    tablaConsulta: string;
    tablaClics: string;
    tablaImpresiones: string;
    tablaCtr: string;
    tablaPosicion: string;
    sinMetricas: string;
}

interface Props {
    restUrl: string;
    nonce: string;
    textos: TextosSearchConsole;
}

/**
 * Bucle de Search Console (Libro Cap. 6.4): conexión OAuth2 real, selección
 * del sitio y sincronización de métricas. Deliberadamente de solo
 * conectar/sincronizar — los consumidores del dato (candidatos de refuerzo,
 * ajuste de asignación) llegan en porciones futuras de la Etapa 5.
 */
export function BloqueSearchConsole({ restUrl, nonce, textos }: Props) {
    const [estado, setEstado] = useState<EstadoSearchConsole | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [aviso, setAviso] = useState<string | null>(null);
    const [clientId, setClientId] = useState('');
    const [clientSecret, setClientSecret] = useState('');
    const [redirectUri, setRedirectUri] = useState<string | null>(null);
    const [urlAutorizacion, setUrlAutorizacion] = useState<string | null>(null);
    const [sitios, setSitios] = useState<SitioSearchConsole[] | null>(null);
    const [sitioElegido, setSitioElegido] = useState('');
    const [enCurso, setEnCurso] = useState(false);

    const cabeceras = { 'X-WP-Nonce': nonce };

    const cargar = () => {
        fetch(`${restUrl}pluma/v1/search-console/estado`, { headers: cabeceras })
            .then((r) => r.json() as Promise<EstadoSearchConsole>)
            .then((json) => {
                setEstado(json);
                setError(null);
            })
            .catch(() => setError(textos.errorCarga));
    };

    useEffect(() => {
        const parametros = new URLSearchParams(window.location.search);
        const resultado = parametros.get('search_console');

        if (null !== resultado) {
            setAviso('conectado' === resultado ? textos.avisoConectado : textos.avisoError);
            parametros.delete('search_console');
            const nuevaBusqueda = parametros.toString();
            window.history.replaceState(null, '', window.location.pathname + (nuevaBusqueda ? `?${nuevaBusqueda}` : '') + window.location.hash);
        }

        cargar();
        // eslint-disable-next-line react-hooks/exhaustive-deps -- solo al montar: lee el parámetro de retorno de Google una sola vez.
    }, []);

    const guardarCredenciales = () => {
        if ('' === clientId.trim() || '' === clientSecret.trim()) {
            return;
        }

        setEnCurso(true);
        fetch(`${restUrl}pluma/v1/search-console/credenciales`, {
            method: 'POST',
            headers: { ...cabeceras, 'Content-Type': 'application/json' },
            body: JSON.stringify({ clientId, clientSecret }),
        })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                return respuesta.json() as Promise<{ redirectUri: string; urlAutorizacion: string }>;
            })
            .then((json) => {
                setRedirectUri(json.redirectUri);
                setUrlAutorizacion(json.urlAutorizacion);
            })
            .catch(() => setError(textos.errorAccion))
            .finally(() => setEnCurso(false));
    };

    const cargarSitios = () => {
        fetch(`${restUrl}pluma/v1/search-console/sitios`, { headers: cabeceras })
            .then((r) => r.json() as Promise<SitioSearchConsole[]>)
            .then((lista) => {
                setSitios(lista);
                if (lista.length > 0) {
                    setSitioElegido(lista[0].siteUrl);
                }
            })
            .catch(() => setError(textos.errorAccion));
    };

    const guardarSitio = () => {
        if ('' === sitioElegido) {
            return;
        }

        fetch(`${restUrl}pluma/v1/search-console/sitio`, {
            method: 'POST',
            headers: { ...cabeceras, 'Content-Type': 'application/json' },
            body: JSON.stringify({ siteUrl: sitioElegido }),
        })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                cargar();
            })
            .catch(() => setError(textos.errorAccion));
    };

    const sincronizar = () => {
        setEnCurso(true);
        fetch(`${restUrl}pluma/v1/search-console/sincronizar`, { method: 'POST', headers: cabeceras })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                cargar();
            })
            .catch(() => setError(textos.errorAccion))
            .finally(() => setEnCurso(false));
    };

    const desconectar = () => {
        // eslint-disable-next-line no-alert -- confirmación de una acción real: borra credenciales, sitio y refresh token cifrados.
        if (!window.confirm(textos.confirmarDesconectar)) {
            return;
        }

        fetch(`${restUrl}pluma/v1/search-console/credenciales`, { method: 'DELETE', headers: cabeceras })
            .then(() => {
                setRedirectUri(null);
                setUrlAutorizacion(null);
                setSitios(null);
                cargar();
            })
            .catch(() => setError(textos.errorAccion));
    };

    if (null !== error) {
        return (
            <p className="pluma-maquinas__aviso" role="alert">
                {error}
            </p>
        );
    }

    if (null === estado) {
        return <p className="pluma-maquinas__cargando">{textos.cargando}</p>;
    }

    return (
        <section className="pluma-maquinas__seccion">
            <h2>{textos.titulo}</h2>
            {aviso && <p className="pluma-search-console__aviso">{aviso}</p>}
            {estado.circuitoAbierto && <p data-estado="advertencia">{textos.circuitoAbierto}</p>}

            {!estado.conectada && (
                <>
                    <label className="pluma-maquinas__campo">
                        {textos.campoClientId}
                        <input type="text" value={clientId} onChange={(evento) => setClientId(evento.target.value)} />
                    </label>
                    <label className="pluma-maquinas__campo">
                        {textos.campoClientSecret}
                        <input type="password" value={clientSecret} onChange={(evento) => setClientSecret(evento.target.value)} />
                    </label>
                    <button type="button" disabled={enCurso || '' === clientId.trim() || '' === clientSecret.trim()} onClick={guardarCredenciales}>
                        {enCurso ? textos.conectando : textos.conectar}
                    </button>
                    {redirectUri && urlAutorizacion && (
                        <div className="pluma-search-console__redirect">
                            <h3>{textos.redirectUriTitulo}</h3>
                            <p>{textos.redirectUriAyuda}</p>
                            <code>{redirectUri}</code>
                            <p>
                                <a href={urlAutorizacion}>{textos.irAGoogle}</a>
                            </p>
                        </div>
                    )}
                </>
            )}

            {estado.conectada && !estado.sitioSeleccionado && (
                <>
                    {null === sitios ? (
                        <button type="button" onClick={cargarSitios}>
                            {textos.elegirSitio}
                        </button>
                    ) : (
                        <>
                            <label className="pluma-maquinas__campo">
                                {textos.elegirSitio}
                                <select value={sitioElegido} onChange={(evento) => setSitioElegido(evento.target.value)}>
                                    {sitios.map((sitio) => (
                                        <option key={sitio.siteUrl} value={sitio.siteUrl}>
                                            {sitio.siteUrl}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <button type="button" disabled={'' === sitioElegido} onClick={guardarSitio}>
                                {textos.guardarSitio}
                            </button>
                        </>
                    )}
                </>
            )}

            {estado.conectada && estado.sitioSeleccionado && (
                <>
                    <p>
                        {textos.sitioActual}: {estado.sitioSeleccionado}
                    </p>
                    <p>
                        {textos.ultimaSincronizacion}: {estado.ultimaSincronizacion ? new Date(estado.ultimaSincronizacion).toLocaleString() : textos.nuncaSincronizado}
                    </p>
                    <button type="button" disabled={enCurso} onClick={sincronizar}>
                        {enCurso ? textos.sincronizando : textos.sincronizarAhora}
                    </button>

                    {0 === estado.metricasRecientes.length ? (
                        <p className="pluma-maquinas__vacio">{textos.sinMetricas}</p>
                    ) : (
                        <table className="pluma-maquinas__tabla">
                            <thead>
                                <tr>
                                    <th>{textos.tablaPagina}</th>
                                    <th>{textos.tablaConsulta}</th>
                                    <th>{textos.tablaClics}</th>
                                    <th>{textos.tablaImpresiones}</th>
                                    <th>{textos.tablaCtr}</th>
                                    <th>{textos.tablaPosicion}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {estado.metricasRecientes.map((metrica) => (
                                    <tr key={`${metrica.postId}-${metrica.consulta}`}>
                                        <td>{metrica.postId}</td>
                                        <td>{metrica.consulta}</td>
                                        <td>{metrica.clics}</td>
                                        <td>{metrica.impresiones}</td>
                                        <td>{(metrica.ctr * 100).toFixed(1)}%</td>
                                        <td>{metrica.posicion.toFixed(1)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </>
            )}

            {estado.conectada && (
                <button type="button" className="pluma-maquinas__boton--quitar" onClick={desconectar}>
                    {textos.desconectar}
                </button>
            )}
        </section>
    );
}
