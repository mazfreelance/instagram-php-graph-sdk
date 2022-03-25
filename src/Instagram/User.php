<?php 

namespace Instagram;

/**
 * Class User
 *
 * @package Instagram
 */
class User {

    /**
     * The user id value.
     *
     * @var int|null
     */
    public $id;

    /**
     * The username value.
     *
     * @var string|null
     */
    public $username;

    /**
     * The acccount type value.
     * Example: PERSONAL | BUSINESS
     *
     * @var string|null
     */
    public $account_type;

    /**
     * The media count value.
     *
     * @var int|null
     */
    public $media_count = 0;

    /**
     * The access token value.
     *
     * @var int|null
     */
    public $token;
    
    /**
     * Create a new user entity.
     *
     * @param string $accessToken
     * @param int    $expiresAt
     */
    public function __construct(int $id, ?string $username = null, ?string $accountType = null, ?int $mediaCount = 0, ?string $token = null)
    {
        $this->id = $id;
        $this->username = $username;
        $this->account_type = $accountType;
        $this->media_count = $mediaCount;
        $this->token = $token;
    }
}