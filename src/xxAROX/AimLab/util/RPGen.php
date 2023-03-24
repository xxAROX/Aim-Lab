<?php
declare(strict_types=1);
namespace xxAROX\AimLab\util;
use pocketmine\resourcepacks\ZippedResourcePack;
use ZipArchive;


/**
 * Class RPGen
 * @package xxAROX\AimLab\util
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 20:25
 * @ide PhpStorm
 * @project Aim-Lab
 */
class RPGen{
	private ZipArchive $archive;
	private string $checksumSource = '';

	private string $path;
	private string $name;
	private string $description;
	private string $author;
	private array $new_version;

	/**
	 * ResourcePackGenerator constructor.
	 * @param string $path
	 * @param string $name
	 * @param string $description
	 * @param null|int[] $new_version
	 */
	public function __construct(string $path, string $name, string $description = "", ?array $new_version = [1,0,0]) {
		$this->path = $path;
		$this->name = $name;
		$this->description = $description;
		$this->author = "xxAROX";
		$this->new_version = $new_version ?? [1,0,0];
		@unlink($this->path);
		$this->archive = new ZipArchive();
		$this->archive->open($this->path, ZipArchive::CREATE);
	}

	public function addFile(string $inPack, string $path): void {
		$this->archive->addFile($path, $inPack);
		$this->checksumSource .= md5_file($inPack);
	}

	public function addFromString(string $inPack, string $content): void {
		$this->archive->addFromString($inPack, $content);
		$this->checksumSource .= $content;
	}

	public function generate(): ZippedResourcePack{
		$this->injectManifest();
		$this->archive->close();
		return new ZippedResourcePack($this->path);
	}

	private function injectManifest(): void {
		$this->addFromString("manifest.json", json_encode([
			'format_version' => 2,
			'header' => [
				'name' => $this->name,
				'uuid' => "e1d76c97-3b6c-4d8f-9448-06e0ec0570bb",
				'description' => $this->description,
				'version' => $this->new_version,
				'min_engine_version' => [1, 16, 0],
				'author' => $this->author
			],
			'modules' => [
				[
					'type' => 'resources',
					'uuid' => "c55c0dd8-0c55-4329-a17e-7691f23a8d81",
					'version' => $this->new_version
				]
			],
		]));
	}
}
