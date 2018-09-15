<?php
/**
 * Created by PhpStorm.
 * User: tio
 * Date: 2018-09-15
 * Time: 16:45
 */

namespace Console;


use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Parser
{

    const SARNA_NET_URL = 'http://www.sarna.net';
    const PLANET_CATEGORY_URL = 'http://www.sarna.net/wiki/Category:Planets?from=';

    /**
     * @var string
     */
    private $planetName;

    public function __construct(string $planetName)
    {
        $this->planetName = $planetName;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getPlanetPage() {
        // We need to instanciate the guzzle client and make the request to the page of the planet category so!
        $client = new Client([
            'timeout' => 1
        ]);

        $firstPlanetLetter = mb_substr(mb_strtoupper($this->planetName), 0, 1);

        $response = $client->request('GET', self::PLANET_CATEGORY_URL . $firstPlanetLetter);

        if ( $response->getStatusCode() == 200 ) {
            $crawler = new Crawler((string) $response->getBody());

            $planetsList = $crawler->filter('body .mw-category-group a');
            foreach ( $planetsList as $node ) {
                if ( mb_strtoupper($node->textContent) == mb_strtoupper($this->planetName) ) {
                    return $node->getAttribute('href');
                }
            }
        }

        throw new \Exception('Planet Name not Found');


    }

    /**
     * @param string $planetUrl
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getPlanetDataInfo(string $planetUrl) {
        $client = new Client();

        $response = $client->request('GET', self::SARNA_NET_URL . $planetUrl);

        if ( $response->getStatusCode() == 200 ) {
            $crawler = new Crawler((string) $response->getBody());

            // We need several information from this page
            // first let's get the planetary info full data so
            $planetaryInfo = $crawler->filter('body h2')->filter('#Planetary_History');
            foreach ( $planetaryInfo as $node ) {
                var_dump($node->textContent);
            }
//            var_dump($planetaryInfo);
//            ->siblings()
//                ->each(function (Crawler $node, $i) {
//                    var_dump($node->text());
//                    return $node;
//                });

            // we need to get all <p> after this until we reach the next h2
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlanetData() {
        $planetPageLink = $this->getPlanetPage();

        $this->getPlanetDataInfo($planetPageLink);
    }

}