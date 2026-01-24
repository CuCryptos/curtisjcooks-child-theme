/**
 * CurtisJCooks Homepage React Application
 * Entry point for the React-powered homepage
 */

import { createRoot } from '@wordpress/element';
import Homepage from './components/Homepage';
import './styles/main.css';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('cjc-react-root');

    if (container) {
        const root = createRoot(container);

        // Get data passed from WordPress
        const wpData = window.cjcData || {};

        root.render(<Homepage data={wpData} />);
    }
});
