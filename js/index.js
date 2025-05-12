import * as fs from 'fs';
import * as path from 'path';
import fastglob from 'fast-glob';
import micromatch from 'micromatch';

/**
 * Generates a message describing the used translations
 * @param {Array<[string, string[]]>} usedTranslations - Array of file paths and their exports
 * @returns {string} Formatted message about translations
 */
function generateUnusedExportsMessage(usedTranslations) {
    const numberOfUsedTranslations = usedTranslations
        .map(([file, exports]) => exports.length)
        .reduce((a, b) => a + b, 0);
    const shouldUsePlural = numberOfUsedTranslations > 1;
    return `
--------------------- Translations found ---------------------
${numberOfUsedTranslations ? `${usedTranslations.map(([file, exports]) => `${file}
    ⟶  ${exports.join(', ')}`).join('\n\n')}
There are ${numberOfUsedTranslations} used translation${shouldUsePlural ? 's' : ''}.
` : `There are no translations found.`}`;
}

/**
 * Ensures a file's directory exists
 * @param {string} file - File path
 * @returns {Promise<void>}
 */
async function ensureFile(file) {
    try {
        await fs.promises.mkdir(path.dirname(file), { recursive: true });
    } catch {
        // ignore error
    }
}

/**
 * Writes data to a JSON file
 * @param {string} file - File path
 * @param {*} data - Data to write
 * @returns {Promise<void>}
 */
async function writeJSON(file, data) {
    await ensureFile(file);
    await fs.promises.writeFile(file, JSON.stringify(data, null, 2) + '\n');
}

/**
 * Searches for files matching the given glob patterns
 * @param {string[]} globs - Glob patterns to match
 * @param {string} [cwd=process.cwd()] - Working directory
 * @returns {string[]} Matched file paths
 */
export function searchGlobs(globs, cwd = process.cwd()) {
    const files = fastglob.sync(globs, {
        absolute: true,
        cwd,
    });
    return process.platform === 'win32'
        ? files.map(file => path.normalize(file))
        : files;
}

/**
 * Filters files based on glob patterns
 * @param {string[]} files - Files to filter
 * @param {string[]} globs - Glob patterns to match
 * @param {string} [cwd=process.cwd()] - Working directory
 * @returns {string[]} Filtered file paths
 */
export function filterGlobs(files, globs, cwd = process.cwd()) {
    return micromatch(
        files.map(file => path.relative(cwd, file)),
        globs,
        { cwd },
    ).map(file => path.normalize(path.join(cwd, file)));
}

/**
 * Creates a Vite plugin for detecting unused code
 * @param {Object} customOptions - Plugin options
 * @param {string} [customOptions.context] - Context directory
 * @param {string[]} [customOptions.patterns] - Glob patterns to include
 * @param {string[]} [customOptions.exclude] - Glob patterns to exclude
 * @param {'all'|'used'|'none'} [customOptions.log] - Logging level
 * @param {string} [customOptions.jsonOutputPath] - Output path for JSON results
 * @param {boolean} [customOptions.dry] - Dry run mode
 * @returns {import('vite').Plugin} Vite plugin
 */
const unusedCodePlugin = (customOptions) => {
    const options = {
        patterns: ['**/*.*'],
        exclude: ['**/utils.js'],
        dry: false,
        jsonOutputPath: 'storage/include-translations.json',
        ...customOptions,
    };

    return {
        enforce: 'post',
        apply: 'build',
        name: 'vite-plugin-unused-code',
        configResolved(config) {
            options.context ??= config.root;
            options.log ??= config.logLevel === 'silent' ? 'none' : (
                config.logLevel === 'info' ? 'all' : 'used'
            );
        },
        generateBundle(outputOptions, bundle) {
            const {
                context = process.cwd(),
                patterns,
                exclude,
                dry,
                log = 'all',
                jsonOutputPath,
            } = options;

            const globs = patterns.concat(exclude.map(pattern => `!${pattern}`));
            const modules = getModules(bundle);
            let usedTranslations = [];

            const usedFiles = filterGlobs([...modules.keys()], globs, context);
            usedTranslations = usedFiles
                .filter(file => modules.get(file).renderedExports.length)
                .map(file => [file, modules.get(file).renderedExports]);

            if (log === 'all' || (log === 'used' && usedTranslations.length)) {
                this.info(generateUnusedExportsMessage(usedTranslations));
            }

            if (!dry) {
                let exportPath = path.join(jsonOutputPath);
                writeJSON(exportPath, usedTranslations
                    .map(([file, exports]) => exports)
                    .reduce((carry, b) => ([...carry, ...b]), []));
            }

            if (usedTranslations.length > 0) {
                this.info('Used translations detected.');
            }
        },
    };
};

/**
 * Cleans up file path by removing query parameters
 * @param {string} id - File path
 * @returns {string} Cleaned file path
 */
function cleanupFilePath(id) {
    const searchIndex = id.indexOf('?');
    return searchIndex === -1 ? id : id.slice(0, searchIndex);
}

/**
 * Finds common elements between two arrays
 * @param {Array} arr1 - First array
 * @param {Array} arr2 - Second array
 * @returns {Array} Array of common elements
 */
function diff(arr1, arr2) {
    return [
        ...arr1.filter(item => arr2.includes(item)),
        ...arr2.filter(item => arr1.includes(item)),
    ];
}

/**
 * Extracts module information from the bundle
 * @param {import('rollup').OutputBundle} bundle - Rollup output bundle
 * @returns {Map<string, {renderedExports: string[]}>} Module information
 */
export function getModules(bundle) {
    const modules = new Map();
    for (const chunk of Object.values(bundle)) {
        const renderedModules = chunk.type === 'chunk' ? chunk.modules : {};
        for (const [id, data] of Object.entries(renderedModules)) {
            const file = cleanupFilePath(id);
            if (path.isAbsolute(file)) {
                const key = path.normalize(file);
                const existing = modules.get(key);
                const fileContent = fs.readFileSync(key, 'utf8');
                let renderedExports = data.renderedExports.filter(name => name !== '__esModule');
                renderedExports = existing ? diff(existing.renderedExports, renderedExports) : renderedExports;
                renderedExports = renderedExports.map(value => {
                    const regex = new RegExp('function\\s+' + value + '\\s*\\/\\*\\s*(\\S*)\\s*(?:\\*\\/)', 'gm');
                    const originalKey = getRegexGroup(regex, fileContent, 1);
                    return originalKey || value;
                });
                modules.set(key, {
                    renderedExports: renderedExports,
                });
            }
        }
    }
    return modules;
}

/**
 * Extracts a specific group from a regex match
 * @param {RegExp} regex - Regular expression
 * @param {string} str - String to search in
 * @param {number} index - Group index to extract
 * @returns {string|null} Matched group or null
 */
function getRegexGroup(regex, str, index) {
    let m;
    while ((m = regex.exec(str)) !== null) {
        if (m.index === regex.lastIndex) {
            regex.lastIndex++;
        }
        for (const match of m) {
            const groupIndex = m.indexOf(match);
            if (groupIndex === index) {
                return match;
            }
        }
    }
    return null;
}

export default unusedCodePlugin;
