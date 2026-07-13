<?php

namespace App\Forms\Admin;

use Tulinkry\Forms;
use Nette\Utils\Html;
use Nette\Utils\Image;
use Tulinkry\Utils\Strings;
use Nette\Utils\Random;

class SliderForm extends Forms\Form
{
	private $slider;

	public function __construct($slider)
	{
		parent::__construct();
		$this->slider = $slider;

		$this->addMultiUpload("images", "Obrázky");

		$this->addSubmit("submit", "Přidat");

	}

	public function process($form)
	{

		if (!$this->presenter->user->isAllowed('backend')) {
			$this->presenter->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
			$this->presenter->redirect(":Front:Pub:default");
			return;
		}

		set_time_limit(0); // long operations

		$values = $form->getValues('array');


		$error = false;
		foreach ($values [ "images" ] as $key => $file) {
			if ($file->isOk() && $file->isImage()) {
				$img = Image::fromFile($file->temporaryFile);
				if ($img->width < 1200 || $img->height < 800) {
					$this->presenter->flashMessage(sprintf("Obrázek '%s' je příliš malý. Nejmenší rozměry jsou %dx%dpx.", $file->name, 1200, 800),
						"error");
					$error = true;
					continue;
				}

				if (!$this->save($img)) {
					$this->presenter->flashMessage(sprintf("Obrázek '%s' je buď poškozený nebo se nejedná o obrázek.", $file->name),
						"error");
					$error = true;
					continue;

				}
				$this->presenter->flashMessage(sprintf("Obrázek '%s' byl uložen", $file->name), "success");
			} else {
				$this->presenter->flashMessage(sprintf("Obrázek '%s' je buď poškozený nebo se nejedná o obrázek.", $file->name),
					"error");
				$error = true;
				continue;
			}
		}

		if (!$error) {
			$this->presenter->flashMessage("Import proběhl v pořádku");
			$this->presenter->redirect("default");
		}
	}


	protected function save(Image $img)
	{
		if (!$img) {
			return false;
		}

		$dirname = WWW_DIR . "/" . $this->slider [ "sliderSrc" ];

		if (!file_exists($dirname)) {
			mkdir($dirname, 0777, true);
		}

		foreach ([ "lg", "sm", "md", "xs" ] as $dir) {
			if (!file_exists($dirname . "/" . $this->slider [ "slider" ] [ $dir ])) {
				mkdir($dirname . "/" . $this->slider [ "slider" ] [ $dir ], 0777, true);
			}
		}


		do {
			$name = Random::generate(10) . ".jpg";
		} while (file_exists($dirname . "/" . $name));

		// Image::save() is void and throws Nette\Utils\ImageException on
		// failure in current Nette (it used to return bool), so the old
		// "$return_value = $return_value && ...->save(...)" chain always
		// evaluated to false/null regardless of whether saving succeeded.
		$return_value = true;
		try {
			$img->save($dirname . "/" . $this->slider [ "slider" ] [ "lg" ] . "/" . $name, 80, Image::JPEG);
			$img->resize(1200, null)
				 ->save($dirname . "/" . $this->slider [ "slider" ] [ "md" ] . "/" . $name, 94, Image::JPEG);
			$img->resize(900, null)
				 ->save($dirname . "/" . $this->slider [ "slider" ] [ "sm" ] . "/" . $name, 94, Image::JPEG);
			$img->resize(600, null)
				 ->save($dirname . "/" . $this->slider [ "slider" ] [ "xs" ] . "/" . $name, 94, Image::JPEG);
		} catch (\Nette\Utils\ImageException $e) {
			$return_value = false;
		}

		if (!$return_value) {
			foreach ([ $dirname . "/" . $this->slider [ "slider" ] [ "lg" ] . "/" . $name,
						$dirname . "/" . $this->slider [ "slider" ] [ "md" ] . "/" . $name,
						$dirname . "/" . $this->slider [ "slider" ] [ "sm" ] . "/" . $name,
						$dirname . "/" . $this->slider [ "slider" ] [ "xs" ] . "/" . $name ] as $image) {
				if (file_exists($image)) {
					@unlink($image);
				}
			}
		}

		return $return_value;
	}
}
