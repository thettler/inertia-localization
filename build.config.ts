import { defineBuildConfig } from 'unbuild'

export default defineBuildConfig({
  entries: ['js/src/index'],
  outDir: 'build',
  clean: true,
  declaration: true,
  rollup: {
    emitCJS: true,
    inlineDependencies: true,
  },
})
