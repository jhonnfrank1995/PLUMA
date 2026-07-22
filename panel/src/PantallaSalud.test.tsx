import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { PantallaSalud, type DatosSalud } from './PantallaSalud';

function datosDeEjemplo(sobrescribir: Partial<DatosSalud> = {}): DatosSalud {
    return {
        versionPhp: '8.2.31',
        versionWordPress: '6.7.1',
        versionBaseDatos: '8.0.36',
        versionEsquemaPlugin: '0.1.0',
        cronRealConfigurado: true,
        esMultisitio: false,
        textos: {
            titulo: 'Sala de Máquinas',
            etiquetaPhp: 'PHP',
            etiquetaWordPress: 'WordPress',
            etiquetaBaseDatos: 'Base de datos',
            etiquetaEsquema: 'Esquema PLUMA',
            etiquetaCron: 'Cron real',
            cronOk: 'Configurado',
            cronAdvertencia: 'WP-Cron activo: no recomendado para producción',
            etiquetaMultisitio: 'Multisitio',
            multisitioSi: 'Sí',
            multisitioNo: 'No',
        },
        ...sobrescribir,
    };
}

describe('PantallaSalud', () => {
    it('muestra las versiones reales recibidas por props', () => {
        render(<PantallaSalud datos={datosDeEjemplo()} />);

        expect(screen.getByText('8.2.31')).toBeInTheDocument();
        expect(screen.getByText('6.7.1')).toBeInTheDocument();
        expect(screen.getByText('8.0.36')).toBeInTheDocument();
        expect(screen.getByText('0.1.0')).toBeInTheDocument();
    });

    it('marca el cron como OK cuando el hosting lo configuró', () => {
        render(<PantallaSalud datos={datosDeEjemplo({ cronRealConfigurado: true })} />);

        const estado = screen.getByText('Configurado');
        expect(estado).toHaveAttribute('data-estado', 'ok');
    });

    it('advierte cuando el cron real NO está configurado (WP-Cron por defecto)', () => {
        render(<PantallaSalud datos={datosDeEjemplo({ cronRealConfigurado: false })} />);

        const estado = screen.getByText('WP-Cron activo: no recomendado para producción');
        expect(estado).toHaveAttribute('data-estado', 'advertencia');
    });

    it('distingue instalación multisitio de instalación simple', () => {
        const { rerender } = render(<PantallaSalud datos={datosDeEjemplo({ esMultisitio: true })} />);
        expect(screen.getByText('Sí')).toBeInTheDocument();

        rerender(<PantallaSalud datos={datosDeEjemplo({ esMultisitio: false })} />);
        expect(screen.getByText('No')).toBeInTheDocument();
    });
});
