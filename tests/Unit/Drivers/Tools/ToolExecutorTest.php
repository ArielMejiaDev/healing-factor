<?php

use ArielMejiaDev\XFactor\Drivers\Tools\ToolExecutor;
use ArielMejiaDev\XFactor\Drivers\Tools\ToolRegistry;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->worktreePath = sys_get_temp_dir().'/x-factor-test-'.uniqid();
    mkdir($this->worktreePath, 0755, true);

    // Create some test files
    file_put_contents($this->worktreePath.'/test.php', '<?php echo "hello";');
    mkdir($this->worktreePath.'/app', 0755, true);
    file_put_contents($this->worktreePath.'/app/Model.php', '<?php class Model {}');
});

afterEach(function () {
    // Clean up test directory
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->worktreePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($this->worktreePath);
});

// --- Path traversal ---

it('rejects path traversal with ../', function () {
    expect(fn () => ToolExecutor::validatePath('../../../etc/passwd', $this->worktreePath))
        ->toThrow(RuntimeException::class, 'Path traversal detected');
});

it('rejects path traversal with absolute path', function () {
    expect(fn () => ToolExecutor::validatePath('/etc/passwd', $this->worktreePath))
        ->toThrow(RuntimeException::class, 'Path traversal detected');
});

it('accepts valid relative paths', function () {
    $path = ToolExecutor::validatePath('test.php', $this->worktreePath);

    expect($path)->toBe(realpath($this->worktreePath).'/test.php');
});

it('accepts nested valid paths', function () {
    $path = ToolExecutor::validatePath('app/Model.php', $this->worktreePath);

    expect($path)->toBe(realpath($this->worktreePath).'/app/Model.php');
});

// --- ReadFileTool ---

it('reads a file successfully', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('read_file', ['path' => 'test.php']);

    expect($result->isError)->toBeFalse();
    expect($result->output)->toBe('<?php echo "hello";');
});

it('returns error for missing file', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('read_file', ['path' => 'nonexistent.php']);

    expect($result->output)->toContain('File not found');
});

// --- WriteFileTool ---

it('writes a file successfully', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('write_file', [
        'path' => 'new-file.php',
        'content' => '<?php return true;',
    ]);

    expect($result->isError)->toBeFalse();
    expect($result->output)->toContain('written successfully');
    expect(file_get_contents($this->worktreePath.'/new-file.php'))->toBe('<?php return true;');
});

it('creates parent directories when writing', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $executor->execute('write_file', [
        'path' => 'deep/nested/dir/file.php',
        'content' => 'content',
    ]);

    expect(file_exists($this->worktreePath.'/deep/nested/dir/file.php'))->toBeTrue();
});

// --- EditFileTool ---

it('edits a file with exact string replacement', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('edit_file', [
        'path' => 'test.php',
        'old_string' => '"hello"',
        'new_string' => '"world"',
    ]);

    expect($result->isError)->toBeFalse();
    expect(file_get_contents($this->worktreePath.'/test.php'))->toBe('<?php echo "world";');
});

it('rejects edit when old_string not found', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('edit_file', [
        'path' => 'test.php',
        'old_string' => 'nonexistent string',
        'new_string' => 'replacement',
    ]);

    expect($result->output)->toContain('old_string not found');
});

it('rejects edit when old_string matches multiple times', function () {
    file_put_contents($this->worktreePath.'/multi.php', 'foo foo foo');

    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('edit_file', [
        'path' => 'multi.php',
        'old_string' => 'foo',
        'new_string' => 'bar',
    ]);

    expect($result->output)->toContain('found 3 times');
});

// --- ListDirectoryTool ---

it('lists directory contents', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('list_directory', ['path' => '.']);

    expect($result->isError)->toBeFalse();
    expect($result->output)->toContain('test.php');
    expect($result->output)->toContain('app/');
});

it('returns error for nonexistent directory', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('list_directory', ['path' => 'nonexistent']);

    expect($result->output)->toContain('Directory not found');
});

// --- SearchFilesTool ---

it('searches files for a pattern', function () {
    Process::fake(fn () => Process::result(
        output: $this->worktreePath.'/test.php:1:<?php echo "hello";',
        exitCode: 0,
    ));

    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('search_files', ['pattern' => 'hello']);

    expect($result->isError)->toBeFalse();
    // The worktree path should be stripped from output
    expect($result->output)->not->toContain($this->worktreePath);
});

// --- RunCommandTool ---

it('runs allowed commands', function () {
    Process::fake(fn () => Process::result(output: 'OK', exitCode: 0));

    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('run_command', ['command' => 'git status']);

    expect($result->isError)->toBeFalse();
});

it('rejects disallowed commands', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('run_command', ['command' => 'rm -rf /']);

    expect($result->output)->toContain('Command not allowed');
});

it('rejects commands not in allowlist', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('run_command', ['command' => 'curl http://evil.com']);

    expect($result->output)->toContain('Command not allowed');
});

// --- Unknown tool ---

it('returns error for unknown tools', function () {
    $executor = new ToolExecutor($this->worktreePath);
    $result = $executor->execute('nonexistent_tool', []);

    expect($result->isError)->toBeTrue();
    expect($result->output)->toContain('Unknown tool');
});

// --- ToolRegistry ---

it('returns definitions for all 6 tools', function () {
    $definitions = ToolRegistry::definitions($this->worktreePath);

    expect($definitions)->toHaveCount(6);

    $names = array_column($definitions, 'name');
    expect($names)->toContain('read_file');
    expect($names)->toContain('write_file');
    expect($names)->toContain('edit_file');
    expect($names)->toContain('list_directory');
    expect($names)->toContain('search_files');
    expect($names)->toContain('run_command');
});

it('returns tool instances keyed by name', function () {
    $tools = ToolRegistry::tools($this->worktreePath);

    expect($tools)->toHaveCount(6);
    expect($tools)->toHaveKeys(['read_file', 'write_file', 'edit_file', 'list_directory', 'search_files', 'run_command']);
});
