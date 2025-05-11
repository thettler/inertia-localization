import path from 'path'
import { fileURLToPath } from 'url'
import * as vite from 'vite'
import { describe, expect, it, vi } from 'vitest'
import unusedCode from '../src'

describe('vite-plugin-unused-code', () => {

  it('should report used translations', async () => {
    const mockConsole = {
      log: vi.fn(),
      info: vi.fn(),
      warn: vi.fn(),
      error: vi.fn(),
    }
    const root = path.dirname(fileURLToPath(import.meta.url))
    await vite.build({
      root,
      plugins: [
        unusedCode({
          dry: true,
          patterns: [
            '**/*.ts',
            '!**/*.test.ts',
          ],
        }),
      ],
      customLogger: vite.createLogger('info', {
        console: mockConsole as unknown as Console,
      }),
    })

    const message = mockConsole.log.mock.calls.map((output: string[]) => output.join('\n')).join('\n')

    expect(message).toEqual(
        expect.stringContaining('Used translations detected.'),
    )
    expect(message).toEqual(
        expect.stringContaining(path.join(root, 'fixtures/partially-used.ts')),
    )
    expect(message).toEqual(
        expect.stringContaining(path.join(root, 'fixtures/used.ts')),
    )
    expect(message).toEqual(
        expect.stringContaining('scream'),
    )
  })
})
