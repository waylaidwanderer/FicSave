<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-09-25
 * Time: 10:51 PM
 */

namespace App\Ficsave;


use App\Enums\DownloadStatus;

class Download implements \JsonSerializable
{
    private $sessionId;
    private $id;
    private $story;
    private $numChapters;
    private $format;
    private $timestamp;
    private $email = '';
    private $status = DownloadStatus::PENDING;
    private $statusMessage = '';
    private $fileName = '';
    private $currentChapter = 1;
    private $chapters = [];

    public function __construct()
    {
        $this->timestamp = time();
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     * @return Download
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Download
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Story
     */
    public function getStory()
    {
        return $this->story;
    }

    /**
     * @param Story $story
     * @return Download
     */
    public function setStory($story)
    {
        $this->story = $story;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumChapters()
    {
        return $this->numChapters;
    }

    /**
     * @param int $numChapters
     * @return Download
     */
    public function setNumChapters($numChapters)
    {
        $this->numChapters = $numChapters;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     * @return Download
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     * @return Download
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Download
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return Download
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * @param string $statusMessage
     * @return Download
     */
    public function setStatusMessage($statusMessage)
    {
        $this->statusMessage = $statusMessage;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return Download
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentChapter()
    {
        return $this->currentChapter;
    }

    /**
     * @param int $currentChapter
     * @return Download
     */
    public function setCurrentChapter($currentChapter)
    {
        $this->currentChapter = $currentChapter;
        return $this;
    }

    /**
     * @return Chapter[]
     */
    public function getChapters()
    {
        return $this->chapters;
    }

    public function clearChapters()
    {
        $this->chapters = [];
        return $this;
    }

    public function addChapter(Chapter $chapter)
    {
        $this->chapters[] = $chapter;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['chapters']);
        return $vars;
    }
}
