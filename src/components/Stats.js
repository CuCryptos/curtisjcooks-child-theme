/**
 * Stats Section with Animated Counters
 */

import { useState, useEffect, useRef } from '@wordpress/element';

// Custom hook for counting animation
function useCounter(end, duration = 2000, start = 0, decimals = 0) {
    const [count, setCount] = useState(start);
    const [isVisible, setIsVisible] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setIsVisible(true);
                }
            },
            { threshold: 0.5 }
        );

        if (ref.current) {
            observer.observe(ref.current);
        }

        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (!isVisible) return;

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
    }, [isVisible, end, duration, start, decimals]);

    return [count, ref, isVisible];
}

function StatItem({ value, label, suffix = '', decimals = 0 }) {
    const [count, ref, isVisible] = useCounter(value, 2000, 0, decimals);

    return (
        <div ref={ref} className={`cjc-stat-item ${isVisible ? 'is-visible' : ''}`}>
            <div className="cjc-stat-number">
                {decimals > 0 ? count : count.toLocaleString()}{suffix}
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
