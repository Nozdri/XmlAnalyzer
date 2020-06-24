<?php


namespace TopRadar\Service;


class CheckXml
{
	private $newFeed;
	private $oldFeed;
	private $offerTemplate;

	public function __construct($newFeed, $oldFeed, $offerTemplate)
	{
		$this->newFeed = $newFeed;
		$this->oldFeed = $oldFeed;
		$this->offerTemplate = $offerTemplate;

	}

	public function check()
	{
		$oldXml = new \DOMDocument();
		$newXml = new \DOMDocument();

		$newXml->loadXML($this->newFeed, LIBXML_COMPACT | LIBXML_PARSEHUGE);
		$newOffers = $newXml->getElementsByTagName('offer');
		$newCategories = $newXml->getElementsByTagName('category');


		$oldXml->loadXML($this->oldFeed, LIBXML_COMPACT | LIBXML_PARSEHUGE);
		$oldOffers = $oldXml->getElementsByTagName('offer');
		$oldCategories = $oldXml->getElementsByTagName('category');

		echo "offers {$newOffers->length} / {$oldOffers->length} \n";
		echo "categories {$newCategories->length} / {$oldCategories->length} \n";

		$this->findErrors($newOffers, $oldOffers);
	}


	/**
	 * @param \DOMNodeList|\DOMElement[] $newOffers
	 * @param \DOMNodeList|\DOMElement[] $oldOffers
	 */
	private function findErrors(\DOMNodeList $newOffers, \DOMNodeList $oldOffers)
	{
		$newOffersCollection = $this->getNewOffersCollection($newOffers);

		$this->tags($newOffersCollection, $oldOffers);

		$this->values($newOffersCollection, $oldOffers);

	}

	private function values($newOffersCollection, $oldOffers)
	{
		$errors = [];

		foreach ($oldOffers as $oldOffer) {

			$oldOfferId = $oldOffer->getAttribute('id');

			foreach ($oldOffer->childNodes as $element) {

				if ($element instanceof \DOMElement && $newOffersCollection->has($oldOfferId)) {

					$newValue = $newOffersCollection->get($oldOfferId);

					if ($element->nodeName !== 'picture'
						&& $element->nodeName !== 'description'
						&& array_search($element->nodeName, $this->offerTemplate)
						&& isset($newValue[$element->nodeName])
						&& $newValue[$element->nodeName] !== $element->nodeValue) {

						$errors[$oldOfferId][$element->nodeName] = [$newValue[$element->nodeName], $element->nodeValue];
					}
				}
			}
		}

		file_put_contents(base_path('/values_result.txt'), print_r($errors, true));
	}

	private function tags($newOffersCollection, $oldOffers)
	{
		$tagErrors = collect();

		foreach ($oldOffers as $oldOffer) {

			$currentNewOffer = $newOffersCollection->get($oldOffer->getAttribute('id'));

			if ($currentNewOffer) {

				$change = [];

				foreach ($oldOffer->childNodes as $element) {

					if ($element instanceof \DOMElement) {

						if (array_search($element->nodeName, $this->offerTemplate) !== false
							&& !isset($currentNewOffer[$element->nodeName])) {

							$change[] = $element->nodeName;
						}
					}
				}

				if (count($change) > 0) {

					$tagErrors->put($oldOffer->getAttribute('id'), $change);
				}
			}
		}

		file_put_contents(base_path('/tag_result.txt'), print_r($tagErrors, true));
	}

	private function getNewOffersCollection(\DOMNodeList $newOffers)
	{

		$newOffersCollection = collect();

		foreach ($newOffers as $newOffer) {

			$newOfferId = $newOffer->getAttribute('id');

			$values = [];

			foreach ($newOffer->childNodes as $element) {

				if ($element instanceof \DOMElement) {

					if ($element->nodeName !== 'picture') {

						$values[$element->nodeName] = $element->nodeValue;
					}
				}
			}

			$newOffersCollection->put($newOfferId, $values);
		}

		return $newOffersCollection;
	}
}