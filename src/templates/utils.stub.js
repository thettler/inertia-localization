/**
 * @typedef {Object} TranslationSettings
 * @property {Object} params
 *
 * @typedef {/*% locales %*/} Locale
 */

/*% imports %*/


/**
 * @param {string} line
 * @param {TranslationSettings} settings
 */
export function trans(line, settings = {}) {
    const replace = settings.params || {};
    if (Object.keys(replace).length === 0) {
        return line;
    }

    let shouldReplace = {};

    for (let [key, value] of Object.entries(replace)) {
        shouldReplace[`:${key.charAt(0).toUpperCase() + key.slice(1)}`] =
            value?.toString().charAt(0).toUpperCase() + value?.toString().slice(1) || '';
        shouldReplace[`:${key.toUpperCase()}`] = value?.toString().toUpperCase() || '';
        shouldReplace[`:${key}`] = value;
    }

    for (let [placeholder, replacement] of Object.entries(shouldReplace)) {
        line = line.split(placeholder).join(replacement);
    }

    return line;
}

/**
 * @param {Object<Locale, string>} line
 * @param {string} key
 * @param {Locale} [locale]
 * @param {TranslationSettings} [args]
 */
export function findTranslation(line, key, locale = undefined, args = {}) {
    locale = getLocale(locale);

    if (line[locale] !== undefined) {
        return trans(line[locale], args);
    }

    return key;
}

/**
 * @param {Locale} [locale]
 */
export function getLocale(locale = undefined) {
    if (locale !== undefined) {
        return locale;
    }

    if (/*% framework_specific_get_locale %*/ !== undefined) {
        return /*% framework_specific_get_locale %*/;
    }

    return "/*% default_locale %*/";
}
