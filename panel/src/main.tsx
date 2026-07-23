import { createRoot } from 'react-dom/client';
import { Aplicacion, type DatosPlumaPanel } from './Aplicacion';
import './estilos.css';

declare global {
    interface Window {
        plumaPanel?: DatosPlumaPanel;
    }
}

const contenedor = document.getElementById('pluma-panel-root');

if (contenedor && window.plumaPanel) {
    createRoot(contenedor).render(<Aplicacion datos={window.plumaPanel} />);
}
