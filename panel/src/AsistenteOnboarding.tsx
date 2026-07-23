import { useEffect, useState } from 'react';
import { BloqueLlaveOpenRouter, type TextosLlaveOpenRouter } from './BloqueLlaveOpenRouter';
import { EstudioDeConducta } from './EstudioDeConducta';
import type { PlantillaResumen, TextosBancoPeriodistas } from './PantallaBancoPeriodistas';

interface EstadoTecnico {
    versionPhp: string;
    versionWordPress: string;
    versionBaseDatos: string;
    cronRealConfigurado: boolean;
    esMultisitio: boolean;
    cron: {
        url: string;
        cabecera: string;
        token: string;
    };
}

interface EstadoMotorLlave {
    openRouter: {
        configurada: boolean;
        ultimosCuatro: string | null;
    };
}

interface ResultadoImportacion {
    importadas: string[];
    yaExistian: string[];
}

interface ResultadoCiclo {
    ejecutado: boolean;
    lotesProcesados: number;
    errores: string[];
}

export interface TextosOnboarding {
    titulo: string;
    saltar: string;
    continuar: string;
    atras: string;
    finalizar: string;
    errorCarga: string;
    acto1: {
        titulo: string;
        etiquetaPhp: string;
        etiquetaWordPress: string;
        etiquetaBaseDatos: string;
        cronOk: string;
        cronAdvertencia: string;
        cronDatosTitulo: string;
        cronUrl: string;
        cronCabecera: string;
        cronComandoTitulo: string;
        recetaCpanelTitulo: string;
        recetaCpanelTexto: string;
        recetaSistemaTitulo: string;
        recetaSistemaTexto: string;
        avisoGenerico: string;
    };
    acto2: {
        titulo: string;
        googleTrendsInfo: string;
    };
    acto3: {
        titulo: string;
        lineaEditorialLabel: string;
        lineaEditorialPlaceholder: string;
        importarCategorias: string;
        importando: string;
        resultadoImportadas: string;
        resultadoYaExistian: string;
        sinCategorias: string;
    };
    acto4: {
        titulo: string;
        elegirPlantilla: string;
        crear: string;
        creando: string;
        ajusteFino: string;
    };
    acto5: {
        titulo: string;
        modoTitulo: string;
        modoPilotoDescripcion: string;
        primerCiclo: string;
        ejecutando: string;
        resultadoTitulo: string;
        sinLotes: string;
    };
}

interface Props {
    restUrl: string;
    nonce: string;
    textos: TextosOnboarding;
    textosModo: { piloto: string; copiloto: string; autonomo: string };
    textosLlave: TextosLlaveOpenRouter;
    textosBancoPeriodistas: TextosBancoPeriodistas;
    alTerminar: () => void;
}

const CABECERAS_JSON = 'application/json';

export function AsistenteOnboarding({ restUrl, nonce, textos, textosModo, textosLlave, textosBancoPeriodistas, alTerminar }: Props) {
    const [acto, setActo] = useState(1);
    const [error, setError] = useState<string | null>(null);
    const [lineaEditorialGlobal, setLineaEditorialGlobal] = useState('');
    const [periodistaCreado, setPeriodistaCreado] = useState<number | null>(null);

    const cabeceras = { 'X-WP-Nonce': nonce };

    const saltar = () => {
        fetch(`${restUrl}pluma/v1/onboarding/completar`, { method: 'POST', headers: cabeceras })
            .then(() => alTerminar())
            .catch(() => setError(textos.errorCarga));
    };

    return (
        <div className="pluma-onboarding">
            <header className="pluma-onboarding__cabecera">
                <h1>{textos.titulo}</h1>
                <button type="button" className="pluma-onboarding__saltar" onClick={saltar}>
                    {textos.saltar}
                </button>
            </header>

            <ol className="pluma-onboarding__pasos" aria-label={textos.titulo}>
                {[1, 2, 3, 4, 5].map((n) => (
                    <li key={n} className={n === acto ? 'pluma-onboarding__paso pluma-onboarding__paso--activo' : 'pluma-onboarding__paso'}>
                        {n}
                    </li>
                ))}
            </ol>

            {error && (
                <p className="pluma-onboarding__error" role="alert">
                    {error}
                </p>
            )}

            {1 === acto && <Acto1 restUrl={restUrl} nonce={nonce} textos={textos.acto1} />}
            {2 === acto && <Acto2 restUrl={restUrl} nonce={nonce} textos={textos.acto2} textosLlave={textosLlave} />}
            {3 === acto && <Acto3 restUrl={restUrl} nonce={nonce} textos={textos.acto3} onLineaEditorial={setLineaEditorialGlobal} />}
            {4 === acto && (
                <Acto4
                    restUrl={restUrl}
                    nonce={nonce}
                    textos={textos.acto4}
                    textosBancoPeriodistas={textosBancoPeriodistas}
                    lineaEditorial={lineaEditorialGlobal}
                    onCreado={setPeriodistaCreado}
                />
            )}
            {5 === acto && (
                <Acto5
                    restUrl={restUrl}
                    nonce={nonce}
                    textos={textos.acto5}
                    textoFinalizar={textos.finalizar}
                    textosModo={textosModo}
                    onFinalizar={saltar}
                />
            )}

            <nav className="pluma-onboarding__nav">
                {acto > 1 && (
                    <button type="button" onClick={() => setActo(acto - 1)}>
                        {textos.atras}
                    </button>
                )}
                {acto < 5 && (
                    <button type="button" disabled={4 === acto && null === periodistaCreado} onClick={() => setActo(acto + 1)}>
                        {textos.continuar}
                    </button>
                )}
            </nav>
        </div>
    );
}

function Acto1({ restUrl, nonce, textos }: { restUrl: string; nonce: string; textos: TextosOnboarding['acto1'] }) {
    const [datos, setDatos] = useState<EstadoTecnico | null>(null);

    useEffect(() => {
        fetch(`${restUrl}pluma/v1/onboarding/estado-tecnico`, { headers: { 'X-WP-Nonce': nonce } })
            .then((r) => r.json() as Promise<EstadoTecnico>)
            .then(setDatos)
            .catch(() => setDatos(null));
    }, [restUrl, nonce]);

    if (null === datos) {
        return null;
    }

    const comando = `curl -X POST "${datos.cron.url}" -H "${datos.cron.cabecera}: ${datos.cron.token}"`;

    return (
        <section className="pluma-onboarding__acto">
            <h2>{textos.titulo}</h2>
            <dl className="pluma-onboarding__lista">
                <div>
                    <dt>{textos.etiquetaPhp}</dt>
                    <dd>{datos.versionPhp}</dd>
                </div>
                <div>
                    <dt>{textos.etiquetaWordPress}</dt>
                    <dd>{datos.versionWordPress}</dd>
                </div>
                <div>
                    <dt>{textos.etiquetaBaseDatos}</dt>
                    <dd>{datos.versionBaseDatos}</dd>
                </div>
            </dl>
            <p data-estado={datos.cronRealConfigurado ? 'ok' : 'advertencia'}>
                {datos.cronRealConfigurado ? textos.cronOk : textos.cronAdvertencia}
            </p>
            <h3>{textos.cronDatosTitulo}</h3>
            <p>
                {textos.cronUrl}: <code>{datos.cron.url}</code>
            </p>
            <p>
                {textos.cronCabecera}: <code>{datos.cron.cabecera}: {datos.cron.token}</code>
            </p>
            <h4>{textos.cronComandoTitulo}</h4>
            <pre>
                <code>{comando}</code>
            </pre>
            <h3>{textos.recetaCpanelTitulo}</h3>
            <p>{textos.recetaCpanelTexto}</p>
            <h3>{textos.recetaSistemaTitulo}</h3>
            <p>{textos.recetaSistemaTexto}</p>
            <p className="pluma-onboarding__aviso">{textos.avisoGenerico}</p>
        </section>
    );
}

function Acto2({
    restUrl,
    nonce,
    textos,
    textosLlave,
}: {
    restUrl: string;
    nonce: string;
    textos: TextosOnboarding['acto2'];
    textosLlave: TextosLlaveOpenRouter;
}) {
    const [estado, setEstado] = useState<EstadoMotorLlave | null>(null);
    const [error, setError] = useState<string | null>(null);

    const cargar = () => {
        fetch(`${restUrl}pluma/v1/motor/estado`, { headers: { 'X-WP-Nonce': nonce } })
            .then((r) => r.json() as Promise<EstadoMotorLlave>)
            .then(setEstado)
            .catch(() => setError(textosLlave.invalida));
    };

    useEffect(cargar, [restUrl, nonce]);

    if (null === estado) {
        return null;
    }

    return (
        <section className="pluma-onboarding__acto">
            <h2>{textos.titulo}</h2>
            <BloqueLlaveOpenRouter
                restUrl={restUrl}
                nonce={nonce}
                configurada={estado.openRouter.configurada}
                ultimosCuatro={estado.openRouter.ultimosCuatro}
                textos={textosLlave}
                alGuardar={cargar}
                alError={() => setError(textosLlave.invalida)}
            />
            <p>{textos.googleTrendsInfo}</p>
            {error && <p role="alert">{error}</p>}
        </section>
    );
}

function Acto3({
    restUrl,
    nonce,
    textos,
    onLineaEditorial,
}: {
    restUrl: string;
    nonce: string;
    textos: TextosOnboarding['acto3'];
    onLineaEditorial: (valor: string) => void;
}) {
    const [lineaEditorial, setLineaEditorial] = useState('');
    const [resultado, setResultado] = useState<ResultadoImportacion | null>(null);
    const [importando, setImportando] = useState(false);

    const importar = () => {
        setImportando(true);
        fetch(`${restUrl}pluma/v1/onboarding/importar-categorias`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce },
        })
            .then((r) => r.json() as Promise<ResultadoImportacion>)
            .then(setResultado)
            .finally(() => setImportando(false));
    };

    return (
        <section className="pluma-onboarding__acto">
            <h2>{textos.titulo}</h2>
            <label className="pluma-onboarding__campo">
                {textos.lineaEditorialLabel}
                <textarea
                    placeholder={textos.lineaEditorialPlaceholder}
                    value={lineaEditorial}
                    onChange={(evento) => {
                        setLineaEditorial(evento.target.value);
                        onLineaEditorial(evento.target.value);
                    }}
                />
            </label>
            <button type="button" disabled={importando} onClick={importar}>
                {importando ? textos.importando : textos.importarCategorias}
            </button>
            {resultado && (
                <dl>
                    <dt>{textos.resultadoImportadas}</dt>
                    <dd>{resultado.importadas.length > 0 ? resultado.importadas.join(', ') : textos.sinCategorias}</dd>
                    <dt>{textos.resultadoYaExistian}</dt>
                    <dd>{resultado.yaExistian.length > 0 ? resultado.yaExistian.join(', ') : textos.sinCategorias}</dd>
                </dl>
            )}
        </section>
    );
}

function Acto4({
    restUrl,
    nonce,
    textos,
    textosBancoPeriodistas,
    lineaEditorial,
    onCreado,
}: {
    restUrl: string;
    nonce: string;
    textos: TextosOnboarding['acto4'];
    textosBancoPeriodistas: TextosBancoPeriodistas;
    lineaEditorial: string;
    onCreado: (id: number) => void;
}) {
    const [plantillas, setPlantillas] = useState<PlantillaResumen[]>([]);
    const [slug, setSlug] = useState('');
    const [creando, setCreando] = useState(false);
    const [periodistaId, setPeriodistaId] = useState<number | null>(null);

    useEffect(() => {
        fetch(`${restUrl}pluma/v1/periodistas/plantillas`, { headers: { 'X-WP-Nonce': nonce } })
            .then((r) => r.json() as Promise<PlantillaResumen[]>)
            .then((lista) => {
                setPlantillas(lista);
                if (lista.length > 0) {
                    setSlug(lista[0].slug);
                }
            });
    }, [restUrl, nonce]);

    const crear = () => {
        setCreando(true);
        fetch(`${restUrl}pluma/v1/periodistas/plantilla`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce, 'Content-Type': CABECERAS_JSON },
            body: JSON.stringify({ plantilla: slug, lineaEditorial }),
        })
            .then((r) => r.json() as Promise<{ id: number }>)
            .then((json) => {
                setPeriodistaId(json.id);
                onCreado(json.id);
            })
            .finally(() => setCreando(false));
    };

    return (
        <section className="pluma-onboarding__acto">
            <h2>{textos.titulo}</h2>
            {null === periodistaId ? (
                <>
                    <label className="pluma-onboarding__campo">
                        {textos.elegirPlantilla}
                        <select value={slug} onChange={(evento) => setSlug(evento.target.value)}>
                            {plantillas.map((plantilla) => (
                                <option key={plantilla.slug} value={plantilla.slug}>
                                    {plantilla.nombre}
                                </option>
                            ))}
                        </select>
                    </label>
                    <button type="button" disabled={creando || '' === slug} onClick={crear}>
                        {creando ? textos.creando : textos.crear}
                    </button>
                </>
            ) : (
                <>
                    <p>{textos.ajusteFino}</p>
                    <EstudioDeConducta
                        restUrl={restUrl}
                        nonce={nonce}
                        periodistaId={periodistaId}
                        textos={textosBancoPeriodistas}
                        onCerrar={() => {
                            /* el asistente avanza con el botón "Continuar", no aquí */
                        }}
                        onCambio={() => {
                            /* nada que refrescar en el asistente */
                        }}
                    />
                </>
            )}
        </section>
    );
}

function Acto5({
    restUrl,
    nonce,
    textos,
    textoFinalizar,
    textosModo,
    onFinalizar,
}: {
    restUrl: string;
    nonce: string;
    textos: TextosOnboarding['acto5'];
    textoFinalizar: string;
    textosModo: { piloto: string; copiloto: string; autonomo: string };
    onFinalizar: () => void;
}) {
    const [modo, setModo] = useState<'piloto' | 'copiloto' | 'autonomo'>('piloto');
    const [ejecutando, setEjecutando] = useState(false);
    const [resultado, setResultado] = useState<ResultadoCiclo | null>(null);

    const guardarModo = (nuevoModo: 'piloto' | 'copiloto' | 'autonomo') => {
        setModo(nuevoModo);
        fetch(`${restUrl}pluma/v1/onboarding/modo`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce, 'Content-Type': CABECERAS_JSON },
            body: JSON.stringify({ modo: nuevoModo }),
        });
    };

    useEffect(() => {
        guardarModo('piloto');
        // eslint-disable-next-line react-hooks/exhaustive-deps -- solo al montar: fija Piloto como modo inicial real, no solo visual.
    }, []);

    const ejecutarPrimerCiclo = () => {
        setEjecutando(true);
        fetch(`${restUrl}pluma/v1/onboarding/primer-ciclo`, { method: 'POST', headers: { 'X-WP-Nonce': nonce } })
            .then((r) => r.json() as Promise<ResultadoCiclo>)
            .then(setResultado)
            .finally(() => setEjecutando(false));
    };

    return (
        <section className="pluma-onboarding__acto">
            <h2>{textos.titulo}</h2>
            <fieldset>
                <legend>{textos.modoTitulo}</legend>
                {(['piloto', 'copiloto', 'autonomo'] as const).map((valor) => (
                    <label key={valor}>
                        <input type="radio" name="modo" value={valor} checked={modo === valor} onChange={() => guardarModo(valor)} />
                        {textosModo[valor]}
                    </label>
                ))}
            </fieldset>
            <p>{textos.modoPilotoDescripcion}</p>
            <button type="button" disabled={ejecutando} onClick={ejecutarPrimerCiclo}>
                {ejecutando ? textos.ejecutando : textos.primerCiclo}
            </button>
            {resultado && (
                <div>
                    <h3>{textos.resultadoTitulo}</h3>
                    <p>
                        {resultado.lotesProcesados > 0 ? resultado.lotesProcesados : textos.sinLotes}
                    </p>
                    {resultado.errores.length > 0 && (
                        <ul>
                            {resultado.errores.map((mensaje) => (
                                <li key={mensaje}>{mensaje}</li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
            <button type="button" onClick={onFinalizar}>
                {textoFinalizar}
            </button>
        </section>
    );
}
