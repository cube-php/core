<?php

namespace Cube\Http\Session\Stores;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Http\Session\SessionStoreInterface;

class FileSessionStore implements SessionStoreInterface
{
    protected string $path;

    public function __construct()
    {
        $this->path = App::getPath(Directory::PATH_CACHE) . '/sessions';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Read session data by id
     *
     * @param string $id Session id
     * @return array
     */
    public function read(string $id): array
    {
        $file = $this->file($id);

        if (!file_exists($file)) {
            return [];
        }

        $data = base64_decode(
            file_get_contents($file)
        );

        return json_decode($data, true) ?? [];
    }

    /**
     * Write session data by id
     *
     * @param string $id Session id
     * @param array $data Session data
     * @return void
     */
    public function write(string $id, array $data): void
    {
        $file = $this->file($id);
        file_put_contents(
            $file,
            base64_encode(json_encode($data)),
            LOCK_EX
        );
    }

    /**
     * Destroy session by id
     *
     * @param string $id Session id
     * @return void
     */
    public function destroy(string $id): void
    {
        $file = $this->file($id);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function purgeExpired(int $lifetime)
    {
        $expires = time() - $lifetime;

        foreach (new \FilesystemIterator($this->path) as $file) {
            if (
                $file->isFile()
                && $file->getExtension() === 'session'
                && $file->getMTime() < $expires
            ) {
                unlink($file->getPathname());
            }
        }
    }

    protected function file(string $id): string
    {
        return $this->path . '/' . $id . '.session';
    }
}
