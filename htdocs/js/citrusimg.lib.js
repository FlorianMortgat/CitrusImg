window.addEventListener('load', () => {
    let LICENSEHELP = {
        'CC0': 'oui, sans conditions [CCLINK]',
        'CCBY': 'oui, mais on doit me créditer [CCLINK]',
        'CCBYNC': 'oui, mais on doit me créditer; utilisation commerciale interdite [CCLINK]',
        'CCBYSA': 'oui, mais on doit me créditer et placer sous licence CC-BY-SA toute œuvre dérivée [CCLINK]',
        'CCBYNCSA': 'oui, mais on doit me créditer; utilisation commerciale interdite; on doit garder CC-BY-NC-SA si on crée une œuvre dérivée [CCLINK]',
        'APSITE': 'uniquement sur agrumes-passion.com et sur citrusimg.f-mo.eu',
    };
    let licenseInp = document.querySelector('input[name="license"]');
    let licenseHelp = document.querySelector('#licenseHelp');
    licenseHelp.style.display = 'None';
    let oncg = (ev) => {
        let content = licenseInp.value;
        let key = content.replace(/[^\w]/g, '');
        if (LICENSEHELP[key]) {
            let helpText = content + ' = ' + LICENSEHELP[key].replace(
                /\[CCLINK\]/,
                '(<a href="https://creativecommons.org/about/cclicenses/">plus d’informations</a>)'
            );
            document.querySelector('#licenseHelp').innerHTML = helpText;
            licenseHelp.style.display = 'block';
        } else {
            licenseHelp.style.display = 'None';
        }
    };
    licenseInp.addEventListener('input', oncg);
    oncg();
});

