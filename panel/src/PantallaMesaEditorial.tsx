import { useCallback, useEffect, useMemo, useState } from 'react';
import { diffLines } from 'diff';

export interface PersonaResumen {
    id: number;
    nombre: string;
}

export interface TarjetaPieza {
    id: number;
    tendenciaTermino: string | null;
    periodista: PersonaResumen | null;
    tesisCorta: string | null;
    tonoDominante: string | null;
    actualizadaEn: string;
}

export type KanbanMesaEditorial = Record<string, TarjetaPieza[]>;

export interface HechoExpediente {
    extracto: string;
    url: string;
    fecha: string;
    nivel: 'verificado' | 'atribuido' | 'disputado';
}

export interface Expediente {
    tendenciaOrigen: string;
    hechos: HechoExpediente[];
}

export interface CandidatoTesis {
    tesis: string;
    puntuacionOriginalidad: number;
    puntuacionCompatibilidadLinea: number;
    puntuacionSustento: number;
    puntuacionConversacional: number;
}

export interface FichaDecisionEditorial {
    periodistaId: number;
    periodistaVersionId: number;
    clasificacion: {
        tema: string;
        gravedad: number;
        polaridad: string;
        novedad: string;
        potencialConversacional: number;
        tipoNoticia: string;
    };
    candidatosTesis: CandidatoTesis[];
    indiceTesisElegida: number;
    tonoDominante: string;
    tonoApoyo: string;
    esqueleto: {
        gancho: string;
        hechosEsencialesConAtribucion: string;
        movimientosArgumentales: string[];
        contraargumentoReconocido: string;
        remate: string;
    };
    creadaEn: string;
}

export interface ResultadoCompuertas {
    aprobada: boolean;
    retenida: boolean;
    motivos: string[];
    modoEfectivo: string;
    calidad: { puntuacionTotal: number; umbral: number; sustentoAprobado: boolean; detalle: string[] };
    riesgo: {
        implicaTragedia: boolean;
        implicaMenores: boolean;
        implicaSalud: boolean;
        implicaViolencia: boolean;
        riesgoDifamacion: boolean;
        detalleDifamacion: string;
        hechosDisputadosSinSenalar: boolean;
        temaRegulado: string | null;
    };
    originalidad: {
        solapamientoConFuentes: boolean;
        solapamientoConSitioPropio: boolean;
        ratioGananciaInformacion: number;
        umbralGananciaMinima: number;
    };
}

export interface AnotacionBorrador {
    punto: string;
    aprobado: boolean;
    detalle: string;
}

export interface BorradorCiclo {
    id: number;
    numeroCiclo: number;
    contenido: string;
    anotaciones: AnotacionBorrador[];
    aprobadoPorCorrector: boolean;
    editadoManualmente: boolean;
    creadoEn: string;
}

export interface DetallePieza {
    id: number;
    estado: string;
    tendenciaTermino: string | null;
    periodista: PersonaResumen | null;
    expediente: Expediente | null;
    fichaDecisionEditorial: FichaDecisionEditorial | null;
    resultadoCompuertas: ResultadoCompuertas | null;
    postId: number | null;
    piezaOriginalId: number | null;
    creadaEn: string;
    actualizadaEn: string;
    borradores: BorradorCiclo[];
    periodistasActivos: PersonaResumen[];
}

export interface TextosMesaEditorial {
    titulo: string;
    cargando: string;
    errorCarga: string;
    errorAccion: string;
    columnaVacia: string;
    sinPeriodista: string;
    sinTesis: string;
    cerrar: string;
    expediente: string;
    sinExpediente: string;
    nivelVerificado: string;
    nivelAtribuido: string;
    nivelDisputado: string;
    ficha: string;
    sinFicha: string;
    tesisElegida: string;
    tonoDominante: string;
    tonoApoyo: string;
    compuertas: string;
    sinCompuertas: string;
    calidad: string;
    riesgo: string;
    originalidad: string;
    motivos: string;
    borradores: string;
    sinBorradores: string;
    cicloAnterior: string;
    cicloActual: string;
    editadoManualmente: string;
    aprobadoPorCorrector: string;
    editar: string;
    guardarEdicion: string;
    cancelar: string;
    contenidoVacio: string;
    reasignar: string;
    reasignarBoton: string;
    aprobar: string;
    descartar: string;
    confirmarDescartar: string;
    actualizacionDe: string;
}

interface Props {
    restUrl: string;
    nonce: string;
    textos: TextosMesaEditorial;
}

const ORDEN_ESTADOS = [
    'detectada',
    'en_investigacion',
    'investigada',
    'en_redaccion',
    'redactada',
    'optimizada',
    'en_revision',
    'aprobada',
    'programada',
    'publicada',
    'retenida',
    'descartada',
    'fallida',
];

const ESTADOS_TERMINALES = new Set(['publicada', 'descartada']);

export function PantallaMesaEditorial({ restUrl, nonce, textos }: Props) {
    const [kanban, setKanban] = useState<KanbanMesaEditorial | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [piezaSeleccionada, setPiezaSeleccionada] = useState<number | null>(null);

    const cabeceras = useMemo(() => ({ 'X-WP-Nonce': nonce }), [nonce]);

    const cargarKanban = useCallback(() => {
        fetch(`${restUrl}pluma/v1/piezas/kanban`, { headers: cabeceras })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                return respuesta.json() as Promise<KanbanMesaEditorial>;
            })
            .then((json) => {
                setKanban(json);
                setError(null);
            })
            .catch(() => setError(textos.errorCarga));
    }, [restUrl, cabeceras, textos.errorCarga]);

    useEffect(() => {
        cargarKanban();
    }, [cargarKanban]);

    if (null !== error) {
        return (
            <div className="pluma-mesa pluma-mesa--error" role="alert">
                {error}
            </div>
        );
    }

    if (null === kanban) {
        return <div className="pluma-mesa pluma-mesa--cargando">{textos.cargando}</div>;
    }

    return (
        <div className="pluma-mesa">
            <h1>{textos.titulo}</h1>

            <div className="pluma-mesa__kanban">
                {ORDEN_ESTADOS.map((estado) => (
                    <div key={estado} className={`pluma-mesa__columna pluma-mesa__columna--${estado}`}>
                        <h2>{estado}</h2>
                        {0 === (kanban[estado] ?? []).length ? (
                            <p className="pluma-mesa__vacio">{textos.columnaVacia}</p>
                        ) : (
                            <ul>
                                {(kanban[estado] ?? []).map((tarjeta) => (
                                    <li key={tarjeta.id}>
                                        <button type="button" className="pluma-mesa__tarjeta" onClick={() => setPiezaSeleccionada(tarjeta.id)}>
                                            <strong>#{tarjeta.id}</strong> {tarjeta.tendenciaTermino ?? '—'}
                                            <span className="pluma-mesa__tarjeta-periodista">{tarjeta.periodista?.nombre ?? textos.sinPeriodista}</span>
                                            <span className="pluma-mesa__tarjeta-tesis">{tarjeta.tesisCorta ?? textos.sinTesis}</span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                ))}
            </div>

            {null !== piezaSeleccionada && (
                <PanelDetalle
                    restUrl={restUrl}
                    cabeceras={cabeceras}
                    piezaId={piezaSeleccionada}
                    textos={textos}
                    onCerrar={() => setPiezaSeleccionada(null)}
                    onCambio={cargarKanban}
                />
            )}
        </div>
    );
}

interface PropsPanelDetalle {
    restUrl: string;
    cabeceras: Record<string, string>;
    piezaId: number;
    textos: TextosMesaEditorial;
    onCerrar: () => void;
    onCambio: () => void;
}

function PanelDetalle({ restUrl, cabeceras, piezaId, textos, onCerrar, onCambio }: PropsPanelDetalle) {
    const [detalle, setDetalle] = useState<DetallePieza | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [enEdicion, setEnEdicion] = useState(false);
    const [borradorEditado, setBorradorEditado] = useState('');
    const [periodistaElegido, setPeriodistaElegido] = useState<string>('');
    const [enCurso, setEnCurso] = useState(false);

    const cargarDetalle = useCallback(() => {
        fetch(`${restUrl}pluma/v1/piezas/${piezaId}`, { headers: cabeceras })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }
                return respuesta.json() as Promise<DetallePieza>;
            })
            .then((json) => {
                setDetalle(json);
                setError(null);
            })
            .catch(() => setError(textos.errorCarga));
    }, [restUrl, cabeceras, piezaId, textos.errorCarga]);

    useEffect(() => {
        cargarDetalle();
    }, [cargarDetalle]);

    const ejecutarAccion = (ruta: string, cuerpo?: Record<string, string>) => {
        setEnCurso(true);

        return fetch(`${restUrl}pluma/v1/piezas/${piezaId}/${ruta}`, {
            method: 'POST',
            headers: { ...cabeceras, 'Content-Type': 'application/json' },
            body: JSON.stringify(cuerpo ?? {}),
        })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('respuesta no OK');
                }

                cargarDetalle();
                onCambio();
            })
            .catch(() => setError(textos.errorAccion))
            .finally(() => setEnCurso(false));
    };

    const guardarEdicion = () => {
        if ('' === borradorEditado.trim()) {
            setError(textos.contenidoVacio);
            return;
        }

        ejecutarAccion('editar', { contenido: borradorEditado }).then(() => setEnEdicion(false));
    };

    const reasignar = () => {
        if ('' === periodistaElegido) {
            return;
        }

        ejecutarAccion('reasignar', { periodistaId: periodistaElegido });
    };

    const descartar = () => {
        // eslint-disable-next-line no-alert -- confirmación de una acción destructiva real, no hay otra forma nativa sin añadir una librería de diálogos.
        if (window.confirm(textos.confirmarDescartar)) {
            ejecutarAccion('descartar');
        }
    };

    if (null !== error && null === detalle) {
        return (
            <div className="pluma-mesa__panel" role="alert">
                {error}
                <button type="button" onClick={onCerrar}>
                    {textos.cerrar}
                </button>
            </div>
        );
    }

    if (null === detalle) {
        return <div className="pluma-mesa__panel">{textos.cargando}</div>;
    }

    const ultimoBorrador = detalle.borradores.length > 0 ? detalle.borradores[detalle.borradores.length - 1] : null;

    return (
        <div className="pluma-mesa__panel" role="dialog" aria-label={`#${detalle.id}`}>
            <header className="pluma-mesa__panel-cabecera">
                <h2>#{detalle.id} — {detalle.tendenciaTermino ?? '—'}</h2>
                <button type="button" onClick={onCerrar}>
                    {textos.cerrar}
                </button>
            </header>

            {null !== error && (
                <p className="pluma-mesa__aviso" role="alert">
                    {error}
                </p>
            )}

            {null !== detalle.piezaOriginalId && (
                <p className="pluma-mesa__insignia-actualizacion">
                    {textos.actualizacionDe} #{detalle.piezaOriginalId}
                </p>
            )}

            <section className="pluma-mesa__seccion">
                <h3>{textos.reasignar}</h3>
                <select
                    aria-label={textos.reasignar}
                    value={periodistaElegido}
                    onChange={(evento) => setPeriodistaElegido(evento.target.value)}
                >
                    <option value="">{detalle.periodista?.nombre ?? textos.sinPeriodista}</option>
                    {detalle.periodistasActivos.map((periodista) => (
                        <option key={periodista.id} value={periodista.id}>
                            {periodista.nombre}
                        </option>
                    ))}
                </select>
                <button type="button" disabled={enCurso || '' === periodistaElegido} onClick={reasignar}>
                    {textos.reasignarBoton}
                </button>
            </section>

            <section className="pluma-mesa__seccion">
                <h3>{textos.ficha}</h3>
                <SeccionFicha ficha={detalle.fichaDecisionEditorial} textos={textos} />
            </section>

            <section className="pluma-mesa__seccion">
                <h3>{textos.expediente}</h3>
                <SeccionExpediente expediente={detalle.expediente} textos={textos} />
            </section>

            <section className="pluma-mesa__seccion">
                <h3>{textos.compuertas}</h3>
                <SeccionCompuertas resultado={detalle.resultadoCompuertas} textos={textos} />
            </section>

            <section className="pluma-mesa__seccion">
                <h3>{textos.borradores}</h3>
                <SeccionBorradores borradores={detalle.borradores} textos={textos} />

                {enEdicion ? (
                    <div className="pluma-mesa__edicion">
                        <textarea
                            value={borradorEditado}
                            onChange={(evento) => setBorradorEditado(evento.target.value)}
                            rows={10}
                        />
                        <div className="pluma-mesa__edicion-botones">
                            <button type="button" disabled={enCurso} onClick={guardarEdicion}>
                                {textos.guardarEdicion}
                            </button>
                            <button type="button" onClick={() => setEnEdicion(false)}>
                                {textos.cancelar}
                            </button>
                        </div>
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={() => {
                            setBorradorEditado(ultimoBorrador?.contenido ?? '');
                            setEnEdicion(true);
                        }}
                    >
                        {textos.editar}
                    </button>
                )}
            </section>

            <section className="pluma-mesa__seccion pluma-mesa__acciones">
                {'retenida' === detalle.estado && (
                    <button type="button" className="pluma-mesa__boton--aprobar" disabled={enCurso} onClick={() => ejecutarAccion('aprobar')}>
                        {textos.aprobar}
                    </button>
                )}
                {!ESTADOS_TERMINALES.has(detalle.estado) && (
                    <button type="button" className="pluma-mesa__boton--descartar" disabled={enCurso} onClick={descartar}>
                        {textos.descartar}
                    </button>
                )}
            </section>
        </div>
    );
}

function SeccionFicha({ ficha, textos }: { ficha: FichaDecisionEditorial | null; textos: TextosMesaEditorial }) {
    if (null === ficha) {
        return <p className="pluma-mesa__vacio">{textos.sinFicha}</p>;
    }

    const tesis = ficha.candidatosTesis[ficha.indiceTesisElegida];

    return (
        <dl>
            <div>
                <dt>{textos.tesisElegida}</dt>
                <dd>{tesis?.tesis ?? '—'}</dd>
            </div>
            <div>
                <dt>{textos.tonoDominante}</dt>
                <dd>{ficha.tonoDominante}</dd>
            </div>
            <div>
                <dt>{textos.tonoApoyo}</dt>
                <dd>{ficha.tonoApoyo}</dd>
            </div>
        </dl>
    );
}

function SeccionExpediente({ expediente, textos }: { expediente: Expediente | null; textos: TextosMesaEditorial }) {
    if (null === expediente || 0 === expediente.hechos.length) {
        return <p className="pluma-mesa__vacio">{textos.sinExpediente}</p>;
    }

    const etiquetaNivel: Record<HechoExpediente['nivel'], string> = {
        verificado: textos.nivelVerificado,
        atribuido: textos.nivelAtribuido,
        disputado: textos.nivelDisputado,
    };

    return (
        <ul className="pluma-mesa__hechos">
            {expediente.hechos.map((hecho, indice) => (
                // eslint-disable-next-line react/no-array-index-key -- el expediente no trae un id propio por hecho.
                <li key={indice} className={`pluma-mesa__hecho pluma-mesa__hecho--${hecho.nivel}`}>
                    <p>{hecho.extracto}</p>
                    <a href={hecho.url} target="_blank" rel="noreferrer noopener">
                        {hecho.url}
                    </a>
                    <span className="pluma-mesa__hecho-nivel">{etiquetaNivel[hecho.nivel]}</span>
                </li>
            ))}
        </ul>
    );
}

function SeccionCompuertas({ resultado, textos }: { resultado: ResultadoCompuertas | null; textos: TextosMesaEditorial }) {
    if (null === resultado) {
        return <p className="pluma-mesa__vacio">{textos.sinCompuertas}</p>;
    }

    return (
        <div className="pluma-mesa__compuertas">
            <div>
                <h4>{textos.calidad}</h4>
                <p>
                    {resultado.calidad.puntuacionTotal} / {resultado.calidad.umbral}
                </p>
            </div>
            <div>
                <h4>{textos.riesgo}</h4>
                <p>{resultado.riesgo.detalleDifamacion || '—'}</p>
            </div>
            <div>
                <h4>{textos.originalidad}</h4>
                <p>{resultado.originalidad.ratioGananciaInformacion.toFixed(2)}</p>
            </div>
            {resultado.motivos.length > 0 && (
                <div className="pluma-mesa__motivos">
                    <h4>{textos.motivos}</h4>
                    <ul>
                        {resultado.motivos.map((motivo) => (
                            <li key={motivo}>{motivo}</li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

function SeccionBorradores({ borradores, textos }: { borradores: BorradorCiclo[]; textos: TextosMesaEditorial }) {
    const [indiceB, setIndiceB] = useState(borradores.length - 1);
    const indiceA = Math.max(0, indiceB - 1);

    if (0 === borradores.length) {
        return <p className="pluma-mesa__vacio">{textos.sinBorradores}</p>;
    }

    const anterior = borradores[indiceA];
    const actual = borradores[indiceB];

    const partes = borradores.length > 1 ? diffLines(anterior.contenido, actual.contenido) : null;

    return (
        <div className="pluma-mesa__borradores">
            <label>
                {textos.cicloActual}
                <select value={indiceB} onChange={(evento) => setIndiceB(Number(evento.target.value))}>
                    {borradores.map((borrador, indice) => (
                        <option key={borrador.id} value={indice}>
                            #{borrador.numeroCiclo}
                        </option>
                    ))}
                </select>
            </label>

            {borradores.length > 1 && (
                <p className="pluma-mesa__ciclo-comparado">
                    {textos.cicloAnterior}: #{anterior.numeroCiclo}
                </p>
            )}

            <p className="pluma-mesa__borrador-meta">
                {actual.editadoManualmente ? textos.editadoManualmente : actual.aprobadoPorCorrector ? textos.aprobadoPorCorrector : ''}
            </p>

            {null === partes ? (
                <pre className="pluma-mesa__contenido">{actual.contenido}</pre>
            ) : (
                <pre className="pluma-mesa__diff">
                    {partes.map((parte, indice) => (
                        // eslint-disable-next-line react/no-array-index-key -- las partes del diff no tienen id propio.
                        <span
                            key={indice}
                            className={parte.added ? 'pluma-mesa__diff-anadido' : parte.removed ? 'pluma-mesa__diff-quitado' : undefined}
                        >
                            {parte.value}
                        </span>
                    ))}
                </pre>
            )}
        </div>
    );
}
