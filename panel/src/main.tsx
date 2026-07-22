import { createRoot } from 'react-dom/client';
import { PantallaSalud, type DatosSalud } from './PantallaSalud';

declare global {
    interface Window {
        plumaSalud?: DatosSalud;
    }
}

const contenedor = document.getElementById('pluma-salud-root');

if (contenedor && window.plumaSalud) {
    createRoot(contenedor).render(<PantallaSalud datos={window.plumaSalud} />);
}
