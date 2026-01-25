/**
 * Stats Section
 */

export default function Stats({ data }) {
    const stats = data?.stats || { recipes: 50, readers: 12000, rating: 4.9 };

    return (
        <section className="cjc-stats-section">
            <div className="cjc-stats-container">
                <div className="cjc-stat-item">
                    <div className="cjc-stat-number">{stats.recipes}+</div>
                    <div className="cjc-stat-label">Hawaiian Recipes</div>
                </div>
                <div className="cjc-stat-item">
                    <div className="cjc-stat-number">{stats.readers.toLocaleString()}+</div>
                    <div className="cjc-stat-label">Monthly Readers</div>
                </div>
                <div className="cjc-stat-item">
                    <div className="cjc-stat-number">{stats.rating} ★</div>
                    <div className="cjc-stat-label">Average Rating</div>
                </div>
            </div>
        </section>
    );
}
