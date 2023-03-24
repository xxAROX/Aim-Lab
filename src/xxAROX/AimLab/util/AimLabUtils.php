<?php
declare(strict_types=1);
namespace xxAROX\AimLab\util;
use FilesystemIterator;
use GdImage;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\utils\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;


/**
 * Class AimLabUtils
 * @package xxAROX\AimLab\util
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 19:09
 * @ide PhpStorm
 * @project Aim-Lab
 */
class AimLabUtils{	static function getBytes(Skin|GdImage|string $image): string{
	if ($image instanceof Skin) $image = self::fromSkinToImage($image);
	if (is_string($image)) $image = imagecreatefrompng($image);
	[$width, $height] = self::getImageSize($image);
	$bytes = "";
	for ($y = 0; $y < $height; $y++) {
		for ($x = 0; $x < $width; $x++) {
			$rgba = @imagecolorat($image, $x, $y);
			$bytes .= chr(($rgba >> 16) & 0xff) . chr(($rgba >> 8) & 0xff) . chr($rgba & 0xff) . chr(((~($rgba >> 24)) << 1) & 0xff);
		}
	}
	@imagedestroy($image);
	return $bytes;
}


	private static function fromSkinToImage(Skin $skin): GdImage|bool{
		return self::toImage($skin->getSkinData(), self::getHeight($skin), self::getWidth($skin));
	}
	private static function toImage(string $data, int $height, int $width): GdImage|bool{
		$pixelArray = str_split(bin2hex($data), 8);
		$image = imagecreatetruecolor($width, $height);
		imagealphablending($image, false);
		imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
		imagesavealpha($image, true);
		$position = count($pixelArray) - 1;
		while (!empty($pixelArray)){
			$x = $position % $width;
			$color = array_map(fn (string $val) => hexdec($val), str_split(array_pop($pixelArray), 2));
			$color[] = ((~((int)array_pop($color))) & 0xff) >> 1;
			imagesetpixel($image, $x, ($position - $x) / $height, imagecolorallocatealpha($image, ...$color));
			$position--;
		}
		return $image;
	}
	private static function getHeight(Skin $skin): int{
		return SkinAdapterSingleton::get()->toSkinData($skin)->getSkinImage()->getHeight();
	}
	private static function getWidth(Skin $skin): int{
		return SkinAdapterSingleton::get()->toSkinData($skin)->getSkinImage()->getWidth();
	}
	private static function getImageSize(GdImage $image): array{
		return [imagesx($image), imagesy($image)];
	}
}
