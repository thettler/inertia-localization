import * as fs from 'fs'
import type {Plugin} from 'vite'
import * as path from 'path'
import fastglob from 'fast-glob'
import micromatch from 'micromatch'
import type {OutputBundle, RenderedModule} from 'rollup'

type ExportsGroup = [string, string[]]

function generateUnusedExportsMessage(usedTranslations: ExportsGroup[]) {
    const numberOfUsedTranslations = usedTranslations
        .map(([file, exports]) => exports.length)
        .reduce((a, b) => a + b, 0);

    const shouldUsePlural = numberOfUsedTranslations > 1;

    return `
--------------------- Translations found ---------------------
${numberOfUsedTranslations ? `${usedTranslations.map(([file, exports]) => `${file}
    ⟶  ${exports.join(', ')}`).join('\n\n')}

There are ${numberOfUsedTranslations} used translation${shouldUsePlural ? 's' : ''}.
` : `There are no translations found.`}`
}

async function ensureFile(file: string) {
    try {
        await fs.promises.mkdir(path.dirname(file), {recursive: true})
    } catch {
        // ignore error
    }
}

async function writeJSON(file: string, data: unknown) {
    await ensureFile(file)
    await fs.promises.writeFile(file, JSON.stringify(data, null, 2) + '\n')
}

export interface Options {
    context?: string,
    patterns?: string[],
    exclude?: string[],
    log?: 'all' | 'used' | 'none',
    jsonOutputPath?: string,
    dry?: boolean,
}

type RequiredExcept<T, U extends keyof T> = Pick<T, U> & Required<Omit<T, U>>

export function searchGlobs(globs: string[], cwd = process.cwd()) {
    const files = fastglob.sync(globs, {
        absolute: true,
        cwd,
    })
    /**
     * `fast-glob` will return absolute paths in POSIX style on Windows
     * {@link https://github.com/micromatch/micromatch?tab=readme-ov-file#backslashes}
     */
    return process.platform === 'win32'
        ? files.map(file => path.normalize(file))
        : files
}

export function filterGlobs(files: string[], globs: string[], cwd = process.cwd()) {
    return micromatch(
        files.map(file => path.relative(cwd, file)),
        globs,
        { cwd },
    ).map(file => path.normalize(path.join(cwd, file)))
}

const unusedCodePlugin = (customOptions: Options): Plugin => {
    const options: RequiredExcept<Options, 'context' | 'log'> = {
        patterns: ['**/*.*'],
        exclude: ['**/utils.js'],
        dry: false,
        jsonOutputPath: 'used-translations.json',
        ...customOptions,
    }
    return {
        enforce: 'post',
        apply: 'build',
        name: 'vite-plugin-unused-code',
        configResolved(config) {
            options.context ??= config.root
            options.log ??= config.logLevel === 'silent' ? 'none' : (
                config.logLevel === 'info' ? 'all' : 'used'
            )
        },
        generateBundle(outputOptions, bundle) {
            const {
                context = process.cwd(),
                patterns,
                exclude,
                dry,
                log = 'all',
                jsonOutputPath,
            } = options

            const globs = patterns.concat(exclude.map(pattern => `!${pattern}`))
            const modules = getModules(bundle)
            let usedTranslations: ExportsGroup[] = []


            const usedFiles = filterGlobs([...modules.keys()], globs, context)
            usedTranslations = usedFiles
                .filter(file => modules.get(file)!.renderedExports.length)
                .map(file => [file, modules.get(file)!.renderedExports])

            if (log === 'all' || log === 'used' && usedTranslations.length) {
                this.info(generateUnusedExportsMessage(usedTranslations))
            }

            if (!dry) {
                let exportPath = path.join(jsonOutputPath)

                writeJSON(exportPath, usedTranslations
                    .map(([file, exports]) =>  exports)
                    .reduce((carry, b) => ([...carry, ...b]), [])
                )
            }

            if (usedTranslations.length > 0) {
                this.info('Used translations detected.')
            }
        },
    }
}

export default unusedCodePlugin


function cleanupFilePath(id: string) {
    const searchIndex = id.indexOf('?')
    return searchIndex === -1 ? id : id.slice(0, searchIndex)
}

function diff<T>(arr1: T[], arr2: T[]) {
    return [
        ...arr1.filter(item => arr2.includes(item)),
        ...arr2.filter(item => arr1.includes(item)),
    ]
}

interface ModuleInfo {
    renderedExports: RenderedModule['renderedExports'],
}

export function getModules(bundle: OutputBundle) {
    const modules = new Map<string, ModuleInfo>()
    const regex = /function\s+nested_translation\s*\/\*\s*(\S*)\s*(\*\/)/gm;

    for (const chunk of Object.values(bundle)) {
        const renderedModules = chunk.type === 'chunk' ? chunk.modules : {}

        for (const [id, data] of Object.entries(renderedModules)) {
            const file = cleanupFilePath(id)

            if (path.isAbsolute(file)) {
                const key = path.normalize(file)
                const existing = modules.get(key)
                const fileContent = fs.readFileSync(key, 'utf8');
                // Used by bundler
                let renderedExports = data.renderedExports.filter(name => name !== '__esModule')
                renderedExports = existing ? diff(existing.renderedExports, renderedExports) : renderedExports

                renderedExports = renderedExports.map(value => {
                        const regex = new RegExp('function\\s+' + value + '\\s*\\/\\*\\s*(\\S*)\\s*(?:\\*\\/)', 'gm');
                        const originalKey = getRegexGroup(regex, fileContent, 1)

                        return originalKey || value
                    }
                )
                modules.set(key, {
                    renderedExports: renderedExports,
                })
            }

        }
    }

    return modules
}

function getRegexGroup(regex: RegExp, str: string, index: number) {
    let m;

    while ((m = regex.exec(str)) !== null) {
        // This is necessary to avoid infinite loops with zero-width matches
        if (m.index === regex.lastIndex) {
            regex.lastIndex++;
        }

        // The result can be accessed through the `m`-variable.
        for (const match of m) {
            const groupIndex = m.indexOf(match);
            if (groupIndex === index) {
                return match;
            }
        }

    }
    return null;

}
