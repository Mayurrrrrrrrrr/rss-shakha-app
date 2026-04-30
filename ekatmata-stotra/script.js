/**
 * Ekatmata Stotra Interactive Script
 * Alternating layout: Left / Center / Right
 */

const stotraText = `
ॐ सच्चिदानन्दरूपाय नमोऽस्तु परमात्मने
ज्योतिर्मयस्वरूपाय विश्वमाङ्गल्यमूर्तये || १ ||

प्रकृतिः पञ्चभूतानि ग्रहा लोकाः स्वरास्तथा
दिशः कालश्च सर्वेषां सदा कुर्वन्तु मङ्गलम्।। २।।

रत्नाकराधौतपदां हिमालयकिरीटिनीम्
ब्रह्मराजर्षिरत्नाढ्यां वन्दे भारतमातरम् || ३ ||

महेन्द्रो मलयः सह्यो देवतात्मा हिमालयः
ध्येयो रैवतको विन्ध्यो गिरिश्चारावलिस्तथा || ४ ||

गङ्गा सरस्वती सिन्धुर्ब्रह्मपुत्रश्च गण्डकी
कावेरी यमुना रेवा कृष्णा गोदा महानदी || ५ ||

अयोध्या मथुरा माया काशीकाञ्ची अवन्तिका
वैशाली द्वारिका ध्येया पुरी तक्षशिला गया || ६ ||

प्रयागः पाटलीपुत्रं विजयानगरं महत्
इन्द्रप्रस्थं सोमनाथः तथाSमृतसरः प्रियम् || ७ ||

चतुर्वेदाः पुराणानि सर्वोपनिषदस्तथा
रामायणं भारतं च गीता सद्दर्शनानि च ॥८॥

जैनागमास्त्रिपिटकाः गुरुग्रन्थः सतां गिरः
एषः ज्ञाननिधिः श्रेष्ठः श्रद्धेयो हृदि सर्वदा ॥९॥

अरुन्धत्यनसूया च सावित्री जानकी सती
द्रौपदी कण्णगी गार्गी मीरा दुर्गावती तथा ॥१०॥

लक्ष्मीरहल्या चन्नम्मा रुद्रमाम्बा सुविक्रमा
निवेदिता सारदा च प्रणम्या मातृदेवताः ॥११॥

श्रीरामो भरतः कृष्णो भीष्मो धर्मस्तथार्जुनः
मार्कण्डेयो हरिश्चन्द्र: प्रह्लादो नारदो ध्रुवः ॥१२॥

हनुमान् जनको व्यासो वसिष्ठश्च शुको बलिः
दधीचिविश्वकर्माणौ पृथुवाल्मीकिभार्गवाः ॥१३॥

भगीरथश्चैकलव्यो मनुर्धन्वन्तरिस्तथा
शिविश्च रन्तिदेवश्च पुराणोद्गीतकीर्तय: ॥१४॥

बुद्धा जिनेन्द्रा गोरक्षः पाणिनिश्च पतञ्जलिः
शङ्करो मध्वनिंबार्कौ श्रीरामानुजवल्लभौ ॥१५॥

झूलेलालोSथ चैतन्यः तिरुवल्लुवरस्तथा
नायन्मारालवाराश्च कंबश्च बसवेश्वरः ॥१६॥

देवलो रविदासश्च कबीरो गुरुनानकः
नरसिस्तुलसीदासो दशमेशो दृढव्रतः ॥१७॥

श्रीमत् शङ्करदेवश्च बन्धू सायणमाधवौ
ज्ञानेश्वरस्तुकारामो रामदासः पुरन्दरः ॥१८॥

बिरसा सहजानन्दो रामानन्दस्तथा महान्
वितरन्तु सदैवैते दैवीं सद्गुणसंपदम् ॥१९॥

भरतर्षिः कालिदासः श्रीभोजो जकणस्तथा
सूरदासस्त्यागराजो रसखानश्च सत्कविः ॥२०॥

रविवर्मा भातखण्डे भाग्यचन्द्रः स भूपतिः
कलावंतश्च विख्याताः स्मरणीया निरन्तरम्॥२१॥

अगस्त्यः कंबुकौण्डिन्यौ राजेन्द्रश्चोलवंशजः
अशोकः पुश्यमित्रश्च खारवेलः सुनीतिमान् ॥२२॥

चाणक्यचन्द्रगुप्तौ च विक्रमः शालिवाहनः
समुद्रगुप्तः श्रीहर्षः शैलेन्द्रो बप्परावलः ॥२३॥

लाचिद्भास्करवर्मा च यशोधर्मा च हूणजित्
श्रीकृष्णदेवरायश्च ललितादित्य उद्बलः ॥२४॥

मुसुनूरिनायकौ तौ प्रतापः शिवभूपतिः
रणजि सिंह इत्येते वीरा विख्यातविक्रमाः ॥२५॥

वैज्ञानिकाश्च कपिलः कणादः सुश्रुतस्तथा
चरको भास्कराचार्यो वराहमिहिरः सुधीः ॥२६॥

नागार्जुनो भरद्वाजः आर्यभट्टो वसुर्बुधः
ध्येयो वेंकटरामश्च विज्ञा रामानुजादयः ॥२७॥

रामकृष्णो दयानन्दो रवीन्द्रो राममोहनः
रामतीर्थोऽरविंदश्च विवेकानन्द उद्यशाः ॥२८॥

दादाभाई गोपबन्धुः तिलको गान्धिरादृताः
रमणो मालवीयश्च श्रीसुब्रह्मण्यभारती ॥२९॥

सुभाषः प्रणवानन्दः क्रान्तिवीरो विनायकः
ठक्करो भीमरावश्च फुले नारायणो गुरुः ॥३०॥

संघशक्तिप्रणेतारौ केशवो माधवस्तथा
स्मरणीयाः सदैवैते नवचैतन्यदायकाः ॥३१॥

अनुक्ता ये भक्ताः प्रभुचरणसंसक्तहृदयाः
अनिर्दष्टा वीराः अधिसमरमुद्ध्वस्तरिपवः
समाजोद्धर्तारः सुहितकरविज्ञाननिपुणाः
नमस्तेभ्यो भूयात् सकलसुजनेभ्यः प्रतिदिनम् ॥ ३२॥

इदमेकात्मतास्तोत्रं श्रद्धया यः सदा पठेत्
स राष्ट्रधर्मनिष्ठावान् अखण्डं भारतं स्मरेत् ॥३३॥
`;

document.addEventListener('DOMContentLoaded', async () => {
    const contentDiv = document.getElementById('stotra-content');
    const backdrop = document.getElementById('backdrop');
    const sidePanel = document.getElementById('side-panel');
    const bottomSheet = document.getElementById('bottom-sheet');
    const sideContent = document.getElementById('side-panel-content');
    const bottomContent = document.getElementById('bottom-sheet-content');
    const closeSide = document.getElementById('close-side-panel');

    let entityData = {};

    // Layout pattern for 33 verses: L=left, C=center, R=right
    // Creates a flowing, manuscript-like rhythm
    const alignments = [
        'center', // 1 - ॐ invocation
        'center', // 2 - प्रकृतिः
        'center', // 3 - भारत माता वंदना
        'left',   // 4 - पर्वत
        'right',  // 5 - नदियाँ
        'left',   // 6 - नगर (1)
        'right',  // 7 - नगर (2)
        'center', // 8 - ग्रन्थ
        'center', // 9 - आगम
        'left',   // 10 - नारी (1)
        'right',  // 11 - नारी (2)
        'left',   // 12 - पुरुष (1)
        'right',  // 13 - पुरुष (2)
        'left',   // 14 - पुरुष (3)
        'right',  // 15 - संत (1)
        'left',   // 16 - संत (2)
        'right',  // 17 - संत (3)
        'left',   // 18 - संत (4)
        'right',  // 19 - संत (5)
        'left',   // 20 - कवि
        'right',  // 21 - कलाकार
        'left',   // 22 - सम्राट (1)
        'right',  // 23 - सम्राट (2)
        'left',   // 24 - सम्राट (3)
        'right',  // 25 - वीर
        'left',   // 26 - वैज्ञानिक (1)
        'right',  // 27 - वैज्ञानिक (2)
        'left',   // 28 - आधुनिक (1)
        'right',  // 29 - आधुनिक (2)
        'left',   // 30 - आधुनिक (3)
        'center', // 31 - संघ
        'center', // 32 - अनुक्त
        'center', // 33 - फल श्रुति
    ];

    try {
        const response = await fetch('data.json');
        entityData = await response.json();

        const verses = stotraText.trim().split(/\n\s*\n/);
        contentDiv.innerHTML = '';

        const entities = Object.keys(entityData).sort((a, b) => b.length - a.length);
        const entityRegex = new RegExp(`(${entities.join('|')})`, 'g');

        verses.forEach((verse, index) => {
            const verseElement = document.createElement('div');
            const alignment = alignments[index] || 'center';
            verseElement.className = `verse align-${alignment}`;

            // Verse number label
            const verseNum = document.createElement('div');
            verseNum.className = 'verse-number';
            verseNum.textContent = `श्लोक ${index + 1}`;
            verseElement.appendChild(verseNum);

            // Verse text
            const verseTextDiv = document.createElement('div');
            verseTextDiv.className = 'verse-text';

            const lines = verse.split('\n');
            let processedLines = lines.map(line => {
                const processedLine = line.replace(entityRegex, (match) => {
                    return `<span class="entity-link" data-entity="${match}">${match}</span>`;
                });
                return `<div>${processedLine}</div>`;
            }).join('');

            verseTextDiv.innerHTML = processedLines;
            verseElement.appendChild(verseTextDiv);

            contentDiv.appendChild(verseElement);
        });

        // Click Handler for Entities
        document.querySelectorAll('.entity-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const entityName = e.target.getAttribute('data-entity');
                showEntityDetails(entityName);
            });
        });

    } catch (error) {
        console.error('Error:', error);
        contentDiv.innerHTML = '<p style="color:#8B2500; text-align:center; padding:2rem;">सामग्री लोड करने में त्रुटि हुई।</p>';
    }

    function showEntityDetails(name) {
        const data = entityData[name];
        if (!data) return;

        const html = `
            <div class="detail-tithi">${data.tithi}</div>
            <h2 class="detail-title">${data.name}</h2>
            <img src="${data.image}" class="detail-image" alt="${data.name}" onerror="this.style.display='none'">
            <p class="detail-summary">${data.summary}</p>
            <a href="${data.link}" target="_blank" rel="noopener" class="detail-link-btn">अधिक जानकारी →</a>
        `;

        sideContent.innerHTML = html;
        bottomContent.innerHTML = html;
        document.body.classList.add('panel-open');
    }

    function closePanels() {
        document.body.classList.remove('panel-open');
    }

    closeSide.addEventListener('click', closePanels);
    backdrop.addEventListener('click', closePanels);

    // Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closePanels();
    });

    // Touch swipe down to close bottom sheet
    let touchStartY = 0;
    bottomSheet.addEventListener('touchstart', (e) => {
        touchStartY = e.touches[0].clientY;
    });

    bottomSheet.addEventListener('touchend', (e) => {
        const touchEndY = e.changedTouches[0].clientY;
        if (touchEndY - touchStartY > 80) {
            closePanels();
        }
    });
});
