document.addEventListener("DOMContentLoaded", function() {
    // 1. Losujemy liczbę od 1 do 6
    const roll = Math.floor(Math.random() * 6) + 1;

    // 2. Definiujemy wspólne atrybuty dla kropki
    const dotAttr = 'r="2" fill="white"';
    let dotsHtml = '';

    // 3. Ustawiamy kropki w zależności od wylosowanej liczby (siatka 24x24)
    switch (roll) {
        case 1:
            dotsHtml = `<circle cx="12" cy="12" ${dotAttr}/>`;
            break;
        case 2:
            dotsHtml = `<circle cx="8" cy="8" ${dotAttr}/><circle cx="16" cy="16" ${dotAttr}/>`;
            break;
        case 3:
            dotsHtml = `<circle cx="8" cy="8" ${dotAttr}/><circle cx="12" cy="12" ${dotAttr}/><circle cx="16" cy="16" ${dotAttr}/>`;
            break;
        case 4:
            dotsHtml = `<circle cx="8" cy="8" ${dotAttr}/><circle cx="16" cy="8" ${dotAttr}/><circle cx="8" cy="16" ${dotAttr}/><circle cx="16" cy="16" ${dotAttr}/>`;
            break;
        case 5:
            dotsHtml = `<circle cx="8" cy="8" ${dotAttr}/><circle cx="16" cy="8" ${dotAttr}/><circle cx="12" cy="12" ${dotAttr}/><circle cx="8" cy="16" ${dotAttr}/><circle cx="16" cy="16" ${dotAttr}/>`;
            break;
        case 6:
            dotsHtml = `<circle cx="8" cy="8" ${dotAttr}/><circle cx="16" cy="8" ${dotAttr}/><circle cx="8" cy="12" ${dotAttr}/><circle cx="16" cy="12" ${dotAttr}/><circle cx="8" cy="16" ${dotAttr}/><circle cx="16" cy="16" ${dotAttr}/>`;
            break;
    }

    // 4. Budujemy cały kod SVG (zielone tło + kropki)
    const svgCode = `
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="2" width="20" height="20" rx="5" fill="#38e17b"/>
                ${dotsHtml}
            </svg>
        `;

    // 5. Wstawiamy do HTML
    const diceIcon = document.getElementById('dice-icon');
    if (diceIcon) {
        diceIcon.innerHTML = svgCode;
    }
});