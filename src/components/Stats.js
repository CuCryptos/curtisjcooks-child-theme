/**
 * Stats Section with Animated Counters
 */

import { useState, useEffect, useRef } from 'react';

// Custom hook for counting animation - starts immediately when mounted
function useCounter(end, duration = 2000, start = 0, decimals = 0) {
    // Start with the end value so something is always visible
    const [count, setCount] = useState(decimals > 0 ? parseFloat(end).toFixed(decimals) : end);
    const [hasAnimated, setHasAnimated] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        // Start animation after a brief delay to ensure mount
        const timer = setTimeout(() => {
            if (hasAnimated) return;
            setHasAnimated(true);

            // Reset to start and animate
            setCount(decimals > 0 ? parseFloat(start).toFixed(decimals) : start);

            let startTime;
            const animate = (timestamp) => {
                if (!startTime) startTime = timestamp;
                const progress = Math.min((timestamp - startTime) / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const currentValue = start + (end - start) * easeOut;

                if (decimals > 0) {
                    setCount(currentValue.toFixed(decimals));
                } else {
                    setCount(Math.floor(currentValue));
                }

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };

            requestAnimationFrame(animate);
        }, 100);

        return () => clearTimeout(timer);
    }, [end, duration, start, decimals, hasAnimated]);

    return [count, ref];
}

function StatItem({ value, label, suffix = '', decimals = 0 }) {
    const [count, ref] = useCounter(value, 2000, 0, decimals);

    // Format the display value
    const displayValue = decimals > 0 ? count : Number(count).toLocaleString();

    return (
        <div ref={ref} className="cjc-stat-item is-visible">
            <div className="cjc-stat-number">
                {displayValue}{suffix}
            </div>
            <div className="cjc-stat-label">{label}</div>
        </div>
    );
}

export default function Stats({ data }) {
    const stats = data?.stats || {
        recipes: 50,
        readers: 12000,
        rating: 4.9,
    };

    return (
        <section className="cjc-stats-section">
            <div className="cjc-stats-container">
                <StatItem value={stats.recipes} label="Hawaiian Recipes" suffix="+" />
                <StatItem value={stats.readers} label="Monthly Readers" suffix="+" />
                <StatItem value={stats.rating} label="Average Rating" suffix=" ★" decimals={1} />
            </div>
        </section>
    );
}
