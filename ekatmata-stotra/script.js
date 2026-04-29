/**
 * Ekatmata Stotra Interactive Script
 */

const stotraText = `
ॐ सच्चिदानन्दरूपाय नमोऽस्तु परमात्मने
ज्योतिर्मयस्वरूपाय विश्वमाङ्गल्यमूर्तये || १ ||

प्रकृतिः पञ्चभूतानि ग्रहा लोकाः स्वरास्तथा
दिशः कालश्च सर्वेषां सदा कुर्वन्तु मङ्गलम्।। २।।

रत्नाकराधौतपदां हिमालयकिरीटिनीम्
ब्रह्मराजर्षिरत्नाढ्यां वन्दे भारतमातरम् || 3 ||

महेन्द्रो मलयः सह्यो देवतात्मा हिमालयः
ध्येयो रैवतको विन्ध्यो गिरिश्चारावलिस्तथा || ४ ||

गङ्गा सरस्वती सिन्धुर्ब्रह्मपुत्रश्च गण्डकी
कावेरी यमुना रेवा कृष्णा गोदा महानदी || ५ ||
`;

document.addEventListener('DOMContentLoaded', async () => {
    const contentDiv = document.getElementById('stotra-content');
    
    try {
        // Load data
        const response = await fetch('data.json');
        const entityData = await response.json();
        
        // Process verses
        const verses = stotraText.trim().split('\n\n');
        contentDiv.innerHTML = ''; // Clear skeleton
        
        verses.forEach((verse, index) => {
            const verseElement = document.createElement('div');
            verseElement.className = 'verse group';
            
            // Split by lines within verse
            const lines = verse.split('\n');
            let processedLines = lines.map(line => {
                // Highlight entities
                let processedLine = line;
                Object.keys(entityData).forEach(entity => {
                    const regex = new RegExp(entity, 'g');
                    processedLine = processedLine.replace(regex, `<span class="entity-link" data-entity="${entity}">${entity}</span>`);
                });
                return `<div>${processedLine}</div>`;
            }).join('');
            
            verseElement.innerHTML = processedLines;
            contentDiv.appendChild(verseElement);
        });

        // Initialize Tippy
        tippy('.entity-link', {
            theme: 'indic',
            animation: 'scale',
            interactive: true,
            allowHTML: true,
            maxWidth: 350,
            content(reference) {
                const entityName = reference.getAttribute('data-entity');
                const data = entityData[entityName];
                
                if (!data) return entityName;

                return `
                    <div class="preview-card">
                        <img src="${data.image}" class="preview-image" alt="${data.name}" onerror="this.src='https://via.placeholder.com/320x160?text=${data.name}'">
                        <div class="preview-body">
                            <div class="preview-tithi">${data.tithi}</div>
                            <h3 class="preview-title">${data.name}</h3>
                            <p class="preview-text">${data.summary}</p>
                            <a href="${data.link}" target="_blank" class="preview-link">
                                Learn more on Bharat Discovery
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>
                `;
            },
            onShow(instance) {
                // Subtle zoom in effect for the card
                instance.popper.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    instance.popper.style.transition = 'transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                    instance.popper.style.transform = 'scale(1)';
                }, 0);
            }
        });

    } catch (error) {
        console.error('Error loading stotra data:', error);
        contentDiv.innerHTML = '<p class="text-red-500">Error loading content. Please refresh.</p>';
    }
});
