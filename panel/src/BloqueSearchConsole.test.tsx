import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { BloqueSearchConsole, type TextosSearchConsole } from './BloqueSearchConsole';

function textosDeEjemplo(): TextosSearchConsole {
    return {
        titulo: 'Search Console',
        cargando: 'Cargando…',
        errorCarga: 'No se pudo cargar Search Console.',
        errorAccion: 'La acción no se pudo completar.',
        avisoConectado: 'Conectado con Google Search Console.',
        avisoError: 'No se pudo completar la conexión.',
        campoClientId: 'Client ID',
        campoClientSecret: 'Client secret',
        conectar: 'Conectar',
        conectando: 'Conectando…',
        redirectUriTitulo: 'URI de redirección',
        redirectUriAyuda: 'Regístrala en Google Cloud.',
        irAGoogle: 'Ir a Google para autorizar',
        elegirSitio: 'Elegir sitio de Search Console',
        guardarSitio: 'Guardar sitio',
        sitioActual: 'Sitio conectado',
        sincronizarAhora: 'Sincronizar ahora',
        sincronizando: 'Sincronizando…',
        ultimaSincronizacion: 'Última sincronización',
        nuncaSincronizado: 'todavía no se ha sincronizado',
        circuitoAbierto: 'en enfriamiento',
        desconectar: 'Desconectar',
        confirmarDesconectar: '¿Desconectar Search Console?',
        tablaPagina: 'Página (post_id)',
        tablaConsulta: 'Consulta',
        tablaClics: 'Clics',
        tablaImpresiones: 'Impresiones',
        tablaCtr: 'CTR',
        tablaPosicion: 'Posición',
        sinMetricas: 'todavía no hay métricas sincronizadas',
    };
}

function estadoNoConectado() {
    return { conectada: false, sitioSeleccionado: null, circuitoAbierto: false, ultimaSincronizacion: null, metricasRecientes: [] };
}

function estadoConectadoSinSitio() {
    return { conectada: true, sitioSeleccionado: null, circuitoAbierto: false, ultimaSincronizacion: null, metricasRecientes: [] };
}

function estadoCompleto() {
    return {
        conectada: true,
        sitioSeleccionado: 'https://sitio.test/',
        circuitoAbierto: false,
        ultimaSincronizacion: '2026-07-23T10:00:00+00:00',
        metricasRecientes: [
            { postId: 12, piezaId: 5, consulta: 'elecciones 2026', clics: 10, impresiones: 200, ctr: 0.05, posicion: 6.2 },
        ],
    };
}

describe('BloqueSearchConsole', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('muestra el formulario de credenciales cuando no está conectado', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve(estadoNoConectado()) }))
        );

        render(<BloqueSearchConsole restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByLabelText('Client ID')).toBeInTheDocument();
        expect(screen.getByLabelText('Client secret')).toBeInTheDocument();
    });

    it('al guardar credenciales muestra el redirectUri y el enlace de autorización', async () => {
        const fetchSimulado = vi.fn((url: string, opciones?: RequestInit) => {
            if (url.endsWith('/search-console/estado')) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve(estadoNoConectado()) });
            }
            if (url.endsWith('/search-console/credenciales') && 'POST' === opciones?.method) {
                return Promise.resolve({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            redirectUri: 'https://ejemplo.test/wp-json/pluma/v1/search-console/callback',
                            urlAutorizacion: 'https://accounts.google.com/o/oauth2/v2/auth?client_id=x',
                        }),
                });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });
        vi.stubGlobal('fetch', fetchSimulado);

        render(<BloqueSearchConsole restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.type(await screen.findByLabelText('Client ID'), 'id-de-prueba');
        await userEvent.type(screen.getByLabelText('Client secret'), 'secreto-de-prueba');
        await userEvent.click(screen.getByRole('button', { name: 'Conectar' }));

        expect(await screen.findByText('https://ejemplo.test/wp-json/pluma/v1/search-console/callback')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Ir a Google para autorizar' })).toHaveAttribute(
            'href',
            'https://accounts.google.com/o/oauth2/v2/auth?client_id=x'
        );
    });

    it('cuando está conectado sin sitio, permite elegir y guardar uno', async () => {
        const fetchSimulado = vi.fn((url: string, opciones?: RequestInit) => {
            if (url.endsWith('/search-console/estado')) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve(estadoConectadoSinSitio()) });
            }
            if (url.endsWith('/search-console/sitios')) {
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve([{ siteUrl: 'https://sitio.test/', permissionLevel: 'siteOwner' }]),
                });
            }
            if (url.endsWith('/search-console/sitio') && 'POST' === opciones?.method) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ sitioSeleccionado: 'https://sitio.test/' }) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });
        vi.stubGlobal('fetch', fetchSimulado);

        render(<BloqueSearchConsole restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByRole('button', { name: 'Elegir sitio de Search Console' }));

        expect(await screen.findByRole('option', { name: 'https://sitio.test/' })).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: 'Guardar sitio' }));

        await waitFor(() =>
            expect(fetchSimulado).toHaveBeenCalledWith(
                'https://ejemplo.test/wp-json/pluma/v1/search-console/sitio',
                expect.objectContaining({ method: 'POST', body: JSON.stringify({ siteUrl: 'https://sitio.test/' }) })
            )
        );
    });

    it('cuando está completo, sincroniza y muestra la tabla de métricas reales', async () => {
        const fetchSimulado = vi.fn((url: string, opciones?: RequestInit) => {
            if (url.endsWith('/search-console/estado')) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve(estadoCompleto()) });
            }
            if (url.endsWith('/search-console/sincronizar') && 'POST' === opciones?.method) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ filasRecibidas: 1, filasGuardadas: 1 }) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });
        vi.stubGlobal('fetch', fetchSimulado);

        render(<BloqueSearchConsole restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByText('elecciones 2026')).toBeInTheDocument();
        expect(screen.getByText('10')).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: 'Sincronizar ahora' }));

        await waitFor(() =>
            expect(fetchSimulado).toHaveBeenCalledWith(
                'https://ejemplo.test/wp-json/pluma/v1/search-console/sincronizar',
                expect.objectContaining({ method: 'POST' })
            )
        );
    });

    it('pide confirmación antes de desconectar', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve(estadoCompleto()) }))
        );
        const confirmSimulado = vi.spyOn(window, 'confirm').mockReturnValue(false);

        render(<BloqueSearchConsole restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByRole('button', { name: 'Desconectar' }));

        expect(confirmSimulado).toHaveBeenCalledWith('¿Desconectar Search Console?');
        confirmSimulado.mockRestore();
    });
});
