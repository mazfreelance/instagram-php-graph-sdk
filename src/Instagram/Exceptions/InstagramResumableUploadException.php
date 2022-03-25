<?php

namespace Instagram\Exceptions;

/**
 * Class InstagramResumableUploadException
 *
 * @package Instagram
 */
class InstagramResumableUploadException extends InstagramSDKException
{
    protected $startOffset;

    protected $endOffset;

    /**
     * @return int|null
     */
    public function getStartOffset()
    {
        return $this->startOffset;
    }

    /**
     * @param int|null $startOffset
     */
    public function setStartOffset($startOffset)
    {
        $this->startOffset = $startOffset;
    }

    /**
     * @return int|null
     */
    public function getEndOffset()
    {
        return $this->endOffset;
    }

    /**
     * @param int|null $endOffset
     */
    public function setEndOffset($endOffset)
    {
        $this->endOffset = $endOffset;
    }
}
