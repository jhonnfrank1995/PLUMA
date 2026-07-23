import { render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { PantallaEstudioSeo, type GrupoCanibalizacion, type PropuestaFusion, type TextosEstudioSeo } from './PantallaEstudioSeo';

function textosDeEjemplo(): TextosEstudioSeo {
    return {
        titulo: 'Estudio SEO y Taxonomía',
        cargando: 'Cargando…',
        errorCarga: 'No se pudo cargar.',
        canibalizacion: {
            titulo: 'Auditoría de canibalización',
            vacio: 'ninguna keyword compartida',
            keyword: 'Keyword principal',
            piezas: 'Piezas publicadas',
        },
        taxonomia: {
            titulo: 'Salud taxonómica',
            cuarentenaTitulo: 'En cuarentena',
            cuarentenaVacio: 'sin cuarentena',
            vecesUsada: 'veces usada',
            fusionTitulo: 'Propuestas de fusión',
            fusionVacio: 'sin propuestas',
            similitud: 'similitud',
        },
        tipo: {
            categoria: 'categoría',
            etiqueta: 'etiqueta',
        },
    };
}

function stubFetch(canibalizacion: GrupoCanibalizacion[], cuarentena: unknown[], propuestasFusion: PropuestaFusion[]) {
    const fetchSimulado = vi.fn((url: string) => {
        if (url.endsWith('/seo/canibalizacion')) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve(canibalizacion) });
        }
        if (url.endsWith('/seo/vocabulario')) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ cuarentena, propuestasFusion }) });
        }
        return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
    });
    vi.stubGlobal('fetch', fetchSimulado);
    return fetchSimulado;
}

describe('PantallaEstudioSeo', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('muestra el vacío cuando no hay canibalización ni propuestas', async () => {
        stubFetch([], [], []);

        render(<PantallaEstudioSeo restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByText('ninguna keyword compartida')).toBeInTheDocument();
        expect(screen.getByText('sin cuarentena')).toBeInTheDocument();
        expect(screen.getByText('sin propuestas')).toBeInTheDocument();
    });

    it('lista los grupos de canibalización con enlaces reales a cada pieza', async () => {
        stubFetch(
            [
                {
                    keywordPrincipal: 'elecciones 2026',
                    piezas: [
                        { piezaId: 1, titulo: 'Primera pieza', url: 'https://sitio.test/primera' },
                        { piezaId: 2, titulo: 'Segunda pieza', url: 'https://sitio.test/segunda' },
                    ],
                },
            ],
            [],
            []
        );

        render(<PantallaEstudioSeo restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByText('elecciones 2026')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Primera pieza' })).toHaveAttribute('href', 'https://sitio.test/primera');
        expect(screen.getByRole('link', { name: 'Segunda pieza' })).toHaveAttribute('href', 'https://sitio.test/segunda');
    });

    it('muestra las etiquetas en cuarentena y las propuestas de fusión', async () => {
        stubFetch(
            [],
            [{ id: 5, tipo: 'etiqueta', nombre: 'IA generativa', vecesUsada: 4 }],
            [{ tipo: 'etiqueta', idA: 1, nombreA: 'Elecciones 2026', idB: 2, nombreB: 'Eleccion 2026', similitud: 92.3 }]
        );

        render(<PantallaEstudioSeo restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByText('IA generativa')).toBeInTheDocument();
        expect(screen.getByText('Elecciones 2026')).toBeInTheDocument();
        expect(screen.getByText('Eleccion 2026')).toBeInTheDocument();
        expect(screen.getByText(/92\.3%/)).toBeInTheDocument();
    });

    it('muestra el error de carga si la petición falla', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.reject(new Error('fallo de red')))
        );

        render(<PantallaEstudioSeo restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByRole('alert')).toHaveTextContent('No se pudo cargar.');
    });
});
