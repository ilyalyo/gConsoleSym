<?php
namespace AppBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RecordRepository")
 * @ORM\Table(name="data")
 */
class Record
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $website;

    /** @ORM\Column(type="string") */
    protected $dateString;
    /** @ORM\Column(type="string") */
    protected $country;
    /** @ORM\Column(type="string") */
    protected $device;
    /** @ORM\Column(type="string") */
    protected $query;
    /** @ORM\Column(type="string") */
    protected $page;
    /** @ORM\Column(type="float") */
    protected $clicks;
    /** @ORM\Column(type="float") */
    protected $impressions;
    /** @ORM\Column(type="float") */
    protected $ctr;
    /** @ORM\Column(type="float") */
    protected $position;

    public function toArray(){
        return [
            "id" => $this->id,
            "date_string" => $this->dateString,
            "country" => $this->country,
            "device" => $this->device,
            "query" => $this->query,
            "page" => $this->page,
            "clicks" => $this->clicks,
            "impressions" => $this->impressions,
            "ctr" => $this->ctr,
            "position" => $this->position,
        ];
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set website
     *
     * @param \AppBundle\Entity\Website $website
     *
     * @return Record
     */
    public function setWebsite(\AppBundle\Entity\Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return \AppBundle\Entity\Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set date
     *
     * @param string $dateString
     *
     * @return Record
     */
    public function setDateString($dateString)
    {
        $this->dateString = $dateString;

        return $this;
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDateString()
    {
        return $this->dateString;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return Record
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set device
     *
     * @param string $device
     *
     * @return Record
     */
    public function setDevice($device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get device
     *
     * @return string
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set query
     *
     * @param string $query
     *
     * @return Record
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set page
     *
     * @param string $page
     *
     * @return Record
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get page
     *
     * @return string
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set clicks
     *
     * @param float $clicks
     *
     * @return Record
     */
    public function setClicks($clicks)
    {
        $this->clicks = $clicks;

        return $this;
    }

    /**
     * Get clicks
     *
     * @return float
     */
    public function getClicks()
    {
        return $this->clicks;
    }

    /**
     * Set impressions
     *
     * @param float $impressions
     *
     * @return Record
     */
    public function setImpressions($impressions)
    {
        $this->impressions = $impressions;

        return $this;
    }

    /**
     * Get impressions
     *
     * @return float
     */
    public function getImpressions()
    {
        return $this->impressions;
    }

    /**
     * Set ctr
     *
     * @param float $ctr
     *
     * @return Record
     */
    public function setCtr($ctr)
    {
        $this->ctr = $ctr;

        return $this;
    }

    /**
     * Get ctr
     *
     * @return float
     */
    public function getCtr()
    {
        return $this->ctr;
    }

    /**
     * Set position
     *
     * @param float $position
     *
     * @return Record
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return float
     */
    public function getPosition()
    {
        return $this->position;
    }
}
