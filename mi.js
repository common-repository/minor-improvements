function mi_rcp3() {
    console.log('Minor Improvements: reCAPTCHA loaded!');
    let actionName = window.location.pathname;
    actionName = actionName.replace(/[^a-zA-Z/]/g, '_');
    grecaptcha.execute(mi_main.mi_recaptcha_site_key, {action: 'mi_' + actionName}).then(function (token) {
        let recaptcha = document.getElementsByClassName('mi-main');
        for (let i = 0; i < recaptcha.length; i++) {
            recaptcha.item(i).value = token;
        }
    });

    setTimeout(mi_rcp3, 1000 * 60);
}
