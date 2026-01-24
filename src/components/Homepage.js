/**
 * CurtisJCooks Homepage Component
 * Main container for all homepage sections
 */

import { useState, useEffect } from '@wordpress/element';
import FloatingParticles from './FloatingParticles';
import Hero from './Hero';
import Stats from './Stats';
import Categories from './Categories';
import FeaturedRecipes from './FeaturedRecipes';
import Newsletter from './Newsletter';
import Footer from './Footer';

export default function Homepage({ data }) {
    const [isLoaded, setIsLoaded] = useState(false);

    useEffect(() => {
        setIsLoaded(true);
    }, []);

    return (
        <div className={`cjc-app ${isLoaded ? 'is-loaded' : ''}`}>
            <FloatingParticles />
            <Hero data={data} />
            <Stats data={data} />
            <Categories data={data} />
            <FeaturedRecipes recipes={data.recipes || []} />
            <Newsletter />
            <Footer />
        </div>
    );
}
