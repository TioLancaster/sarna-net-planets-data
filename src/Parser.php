<?php
/**
 * Created by PhpStorm.
 * User: tio
 * Date: 2018-09-15
 * Time: 16:45
 */

namespace Console;


use GuzzleHttp\Client;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\DomCrawler\Crawler;

class Parser
{

    const SARNA_NET_URL = 'http://www.sarna.net';
    const PLANET_CATEGORY_URL = 'http://www.sarna.net/wiki/Category:Planets?from=';

    const CACHE_KEYS_CATEGORY = 'letter_';
    const CACHE_KEYS_PLANET_URL = 'planet_';

    const SPECTRAL_CLASS = 'Spectral class';
    const JUMP_POINT_DISTANCE = 'Jump pointdistance';
    const GRAVITY = 'Surface gravity';
    const MOONS = 'Moons';
    const POPULATION = 'Population';
    const RECHARGE_STATIONS = 'Recharge station(s)';
    const TECH_LEVEL = 'Socio-Industrial Levels';
    const RESOURCES = 'Socio-Industrial Levels';
    const MINING = 'Socio-Industrial Levels';
    const AGRICULTURE = 'Socio-Industrial Levels';
    const AQUACULTURE = 'Socio-Industrial Levels';
    const RESEARCH_FACILITY = 'Socio-Industrial Levels';
    const MANUFACTURING = 'Socio-Industrial Levels';


    /**
     * @var string
     */
    private $planetName;

    private $cache;

    public function __construct(string $planetName)
    {
        $this->planetName = $planetName;
        $this->cache = new FilesystemCache('', 3600, 'cache');
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

        $cacheKey = self::CACHE_KEYS_CATEGORY . $firstPlanetLetter;

        $content = "";

        if ( $this->cache->has($cacheKey) ) {
            $content = $this->cache->get($cacheKey);
        } else {
            $response = $client->request('GET', self::PLANET_CATEGORY_URL . $firstPlanetLetter);

            if ( $response->getStatusCode() == 200 ) {
                $content = (string) $response->getBody();

                $this->cache->set($cacheKey, $content);
            } else {
                throw new \Exception('Planet Name not Found');
            }
        }

        if ( $content != "" ) {
            $crawler = new Crawler($content);

            $planetsList = $crawler->filter('body .mw-category-group a');
            foreach ( $planetsList as $node ) {
                if ( mb_strtoupper($node->textContent) == mb_strtoupper($this->planetName) ) {
                    return $node->getAttribute('href');
                }
            }
        } else {
            throw new \Exception('Planet Name not Found');
        }


    }

    /**
     * @param string $planetUrl
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getPlanetDataInfo(string $planetUrl) {
        $client = new Client();

        $cacheKey = self::CACHE_KEYS_PLANET_URL . $this->planetName;

        $content = "";

        if ( $this->cache->has($cacheKey) ) {
            $content = $this->cache->get($cacheKey);
        } else {
            $response = $client->request('GET', self::SARNA_NET_URL . $planetUrl);

            if ( $response->getStatusCode() == 200 ) {

                $content = (string) $response->getBody();
                $this->cache->set($cacheKey, $content);
            }
        }


        $crawler = new Crawler($content);

        $planetaryInfo = $crawler->filter('body h2')->filter('#Planetary_History');

        // Let's start by getting the description
        foreach ( $planetaryInfo as $info ) {
            if ( $info->parentNode->nodeName == "h2" ) {
                // we now have to iterate over the siblings nodes of the h2 until the next h2 element so

                $node = $info->parentNode->nextSibling;

                $entireText = '';

                while ( $node ) {
                    if ( $node->nodeName == "h2" ) {
                        break;
                    }
                    $entireText .= $node->textContent . PHP_EOL;
                    $node = $node->nextSibling;
                }

            } else {
                throw new \Exception('Parent node not found.');
            }


            break;
        }

        // Now we need to get the planet information on the side bar so!


        $planetDefinitions = [];

        $tableInfos = $crawler->filter('body table.infobox tr.infoboxrow');
        foreach ( $tableInfos as $tableInfo ) {
            // We know there are always 4 nodes, 2 text and 2 td's we only want the td's so

            $result = $this->parseColumnData($tableInfo->childNodes);

            if ( is_array($result) ) {
                $planetDefinitions[] = $result;
            }
        }

        dump($planetDefinitions);

    }

    /**
     * @param \DOMNode[] $nodes
     */
    private function parseColumnData($nodes) {
        // The columns we are searching for
        //PlanetName
        //x
        //y
        //Faction3025
        //Faction3040
        //Description
        //StarType
        //Difficulty
        //JumpDistance
        //Gravity
        //ClimateBiome
        //ExtensiveVulcanism
        //PlanetwideForest
        //PlanetwideMudflats
        //DenseCloudLayer
        //DominantFungus
        //HallucinatoryVegetation
        //PlanetwideStorms
        //TaintedAtmosphere
        //Asteroids
        //Comet
        //Gasgiant
        //PlanetRings
        //NumberOfMoons
        //Population
        //MegaCity
        //CapitalSystem
        //RechargeStation
        //TechLevel
        //Resources
        //Recreation
        //Mining
        //Agriculture
        //Aquaculture
        //ResearchFacility
        //Manufacturing
        //ComstarBase
        //StarleagueRemnants
        //TradeHub
        //AlienVegetation
        //BlackMarket
        //GeothermalBoreholes
        //RecentlyColonized
        //PiratePresence
        //PrisonPlanet
        //Ruins

        if ( count($nodes) == 4 ) {

            $dataToStore = trim($nodes[3]->textContent);
            $key = '';

            switch ( trim($nodes[1]->textContent) ) {
                case self::SPECTRAL_CLASS:
                    $key = self::SPECTRAL_CLASS;

                    // we only need the first letter for this one so!
                    $dataToStore = mb_substr($dataToStore, 0, 1);
                    break;
                case self::JUMP_POINT_DISTANCE:
                    $key = self::JUMP_POINT_DISTANCE;
                    // we need to convert the value to a float and then round it so!
                    $dataToStore = (int) round((float) $dataToStore);
                    break;
                case self::GRAVITY:
                    $key = self::GRAVITY;

                    $dataToStore = (float) $dataToStore;

                    if ( $dataToStore < 1 ) {
                        $dataToStore = 'Low Gravity Planet';
                    } else if ( $dataToStore == 1 ) {
                        $dataToStore = 'Medium Gravity Planet';
                    } else if ( $dataToStore > 1 ) {
                        $dataToStore = 'High Gravity Planet';
                    }

                    break;
                case self::MOONS:
                    $key = self::MOONS;
                    $dataToStore = (int) $dataToStore;
                    break;
                case self::POPULATION:
                    $key = self::POPULATION;

                    $dataToStore = (int) str_replace(".", "", str_replace(",", "", $dataToStore));

                    if ( $dataToStore < 1000000 ) {
                        $dataToStore = 'Less Than A Million';
                    } else if ( 1000000 <= $dataToStore && $dataToStore <= 100000000 ) {
                        $dataToStore = 'Millions';
                    } else if ( 100000000 < $dataToStore && $dataToStore <= 999999999 ) {
                        $dataToStore = 'Hundreds Of Millions';
                    } else if (  $dataToStore > 999999999 ) {
                        $dataToStore = 'Billions';
                    }

                    break;
                case self::RECHARGE_STATIONS:
                    $key = self::RECHARGE_STATIONS;

                    if ( mb_strpos($dataToStore, 'none') !== false ) {
                        $dataToStore = 'FALSE';
                    } else {
                        $dataToStore = 'TRUE';
                    }

                    break;
                default:
                    return null;
            }


            return [
                'type' => $key,
                'content' => $dataToStore
            ];
        } else {
            throw new \Exception('Row of data in incorrect format');
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