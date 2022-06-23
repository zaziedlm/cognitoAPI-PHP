<?php

use Aws\AwsClient;
use Aws\Result;
use Carbon\Carbon;
use phpseclib3\Math\BigInteger;

class AwsCognitoIdentitySRP
{
    const N_HEX = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD1'.
        '29024E088A67CC74020BBEA63B139B22514A08798E3404DD'.
        'EF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245'.
        'E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED'.
        'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3D'.
        'C2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F'.
        '83655D23DCA3AD961C62F356208552BB9ED529077096966D'.
        '670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B'.
        'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9'.
        'DE2BCBF6955817183995497CEA956AE515D2261898FA0510'.
        '15728E5A8AAAC42DAD33170D04507A33A85521ABDF1CBA64'.
        'ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7'.
        'ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6B'.
        'F12FFA06D98A0864D87602733EC86A64521F2B18177B200C'.
        'BBE117577A615D6C770988C0BAD946E208E24FA074E5AB31'.
        '43DB5BFCE0FD108E4B82D120A93AD2CAFFFFFFFFFFFFFFFF';

    const G_HEX = '2';

    const INFO_BITS = 'Caldera Derived Key';

    /**
     * @var \phpseclib3\Math\BigInteger
     */
    protected $N;

    /**
     * @var \phpseclib3\Math\BigInteger
     */
    protected $g;

    /**
     * @var \phpseclib3\Math\BigInteger
     */
    protected $k;

    /**
     * @var \phpseclib3\Math\BigInteger
     */
    protected $a;

    /**
     * @var \phpseclib3\Math\BigInteger
     */
    protected $A;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $poolName;

    /**
     * @var \Aws\AwsClient
     */
    protected $client;

    /**
     * Create new AWS CognitoIDP instance.
     *
     * @param  \Aws\AwsClient  $client
     * @param  string  $clientId
     * @param  string  $poolName
     * @return void
     */
    public function __construct(AwsClient $client, string $clientId, string $poolName)
    {
        $this->N = new BigInteger(static::N_HEX, 16);
        $this->g = new BigInteger(static::G_HEX, 16);
        $this->k = new BigInteger($this->hexHash('00'.static::N_HEX.'0'.static::G_HEX), 16);

        $this->smallA();
        $this->largeA();

        $this->client = $client;
        $this->clientId = $clientId;
        $this->poolName = $poolName;
    }

    /**
     * Get random a value.
     *
     * @return \phpseclib3\Math\BigInteger
     */
    public function smallA(): BigInteger
    {
        if (is_null($this->a)) {
            $this->a = $this->generateRandomSmallA();
        }

        return $this->a;
    }

    /**
     * Get the client's public value A with the generated random number a.
     *
     * @return \phpseclib3\Math\BigInteger
     */
    public function largeA(): BigInteger
    {
        if (is_null($this->A)) {
            $this->A = $this->calculateA($this->smallA());
        }

        return $this->A;
    }

    /**
     * Generate random bytes as hexadecimal string.
     *
     * @param  int  $bytes
     * @return \phpseclib3\Math\BigInteger
     */
    public function bytes(int $bytes = 32): BigInteger
    {
        $bytes = bin2hex(random_bytes($bytes));

        return new BigInteger($bytes, 16);
    }

    /**
     * Converts a BigInteger (or hex string) to hex format padded with zeroes for hashing.
     *
     * @param  \phpseclib3\Math\BigInteger|string  $longInt
     * @return string
     */
    public function padHex($longInt): string
    {
        $hashStr = $longInt instanceof BigInteger ? $longInt->toHex() : $longInt;

        if (strlen($hashStr) % 2 === 1) {
            $hashStr = '0'.$hashStr;
        } elseif (strpos('89ABCDEFabcdef', $hashStr[0] ?? '') !== false) {
            $hashStr = '00'.$hashStr;
        }

        return $hashStr;
    }

    /**
     * Calculate a hash from a hex string.
     *
     * @param  string  $value
     * @return string
     */
    public function hexHash(string $value): string
    {
        return $this->hash(hex2bin($value));
    }

    /**
     * Calculate a hash from string.
     *
     * @param  string  $value
     * @return string
     */
    public function hash($value): string
    {
        $hash = hash('sha256', $value);

        return str_repeat('0', 64 - strlen($hash)).$hash;
    }

    /**
     * Performs modulo between big integers.
     *
     * @param  \phpseclib3\Math\BigInteger  $a
     * @param  \phpseclib3\Math\BigInteger  $b
     * @return \phpseclib3\Math\BigInteger
     */
    protected function mod(BigInteger $a, BigInteger $b): BigInteger
    {
        return $a->powMod(new BigInteger(1), $b);
    }

    /**
     * Generate a random big integer.
     *
     * @return \phpseclib3\Math\BigInteger
     */
    public function generateRandomSmallA(): BigInteger
    {
        return $this->mod($this->bytes(128), $this->N);
    }

    /**
     * Calculate the client's public value A = g^a%N.
     *
     * @param  \phpseclib3\Math\BigInteger  $a
     * @return \phpseclib3\Math\BigInteger
     *
     * @throws \InvalidArgumentException
     */
    public function calculateA(BigInteger $a): BigInteger
    {
        $A = $this->g->powMod($a, $this->N);

        if ($this->mod($a, $this->N)->equals(new BigInteger(0))) {
            throw new \InvalidArgumentException('Public key failed A mod N == 0 check.');
        }

        return $A;
    }

    /**
     * Calculate the client's value U which is the hash of A and B.
     *
     * @param  \phpseclib3\Math\BigInteger  $A
     * @param  \phpseclib3\Math\BigInteger  $B
     * @return \phpseclib3\Math\BigInteger
     */
    public function calculateU(BigInteger $A, BigInteger $B): BigInteger
    {
        $A = $this->padHex($A);
        $B = $this->padHex($B);

        return new BigInteger($this->hexHash($A.$B), 16);
    }

    /**
     * Extract the pool ID from pool name.
     *
     * @return null|string
     */
    protected function poolId(): ?string
    {
        return explode('_', $this->poolName)[1] ?? null;
    }

    /**
     * Authenticate user with given username and password.
     *
     * @param  string  $username
     * @param  string  $password
     * @return \Aws\Result
     *
     * @throws \RuntimeException
     */
    public function authenticateUser(string $username, string $password): Result
    {
        $result = $this->client->initiateAuth([
            'AuthFlow' => 'USER_SRP_AUTH',
            'ClientId' => $this->clientId,
            'UserPoolId' => $this->poolName,
            'AuthParameters' => [
                'USERNAME' => $username,
                'SRP_A' => $this->largeA()->toHex(),
            ],
        ]);

        if ($result->get('ChallengeName') != 'PASSWORD_VERIFIER') {
            throw new \RuntimeException("ChallengeName `{$result->get('ChallengeName')}` is not supported.");
        }

        return $this->client->respondToAuthChallenge([
            'ChallengeName' => 'PASSWORD_VERIFIER',
            'ClientId' => $this->clientId,
            'ChallengeResponses' => $this->processChallenge($result, $password)
        ]);
    }

    /**
     * Generate authentication challenge response params.
     *
     * @param  \Aws\Result  $result
     * @param  string  $password
     * @return array
     */
    protected function processChallenge(Result $result, string $password): array
    {
        $challengeParameters = $result->get('ChallengeParameters');
        $time = Carbon::now('UTC')->format('D M j H:i:s e Y');
        $secretBlock = base64_decode($challengeParameters['SECRET_BLOCK']);
        $userId = $challengeParameters['USER_ID_FOR_SRP'];

        $hkdf = $this->getPasswordAuthenticationKey(
            $userId,
            $password,
            $challengeParameters['SRP_B'],
            $challengeParameters['SALT']
        );

        $msg = $this->poolId().$userId.$secretBlock.$time;
        $signature = hash_hmac('sha256', $msg, $hkdf, true);

        return [
            'TIMESTAMP' => $time,
            'USERNAME' => $userId,
            'PASSWORD_CLAIM_SECRET_BLOCK' => $challengeParameters['SECRET_BLOCK'],
            'PASSWORD_CLAIM_SIGNATURE' => base64_encode($signature),
        ];
    }

    /**
     * Calculates the final hkdf based on computed S value, and computed U value and the key.
     *
     * @param  string  $username
     * @param  string  $password
     * @param  string  $server
     * @param  string  $salt
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function getPasswordAuthenticationKey(string $username, string $password, string $server, string $salt): string
    {
        $u = $this->calculateU($this->largeA(), $serverB = new BigInteger($server, 16));

        if ($u->equals(new BigInteger(0))) {
            throw new \RuntimeException('U cannot be zero.');
        }

        $usernamePassword = sprintf('%s%s:%s', $this->poolId(), $username, $password);
        $usernamePasswordHash = $this->hash($usernamePassword);

        $x = new BigInteger($this->hexHash($this->padHex($salt).$usernamePasswordHash), 16);
        $gModPowXN = $this->g->modPow($x, $this->N);
        $intValue2 = $serverB->subtract($this->k->multiply($gModPowXN));
        $s = $intValue2->modPow($this->smallA()->add($u->multiply($x)), $this->N);

        return $this->computeHkdf(
            hex2bin($this->padHex($s)),
            hex2bin($this->padHex($u))
        );
    }

    /**
     * Standard hkdf algorithm.
     *
     * @param  string  $ikm
     * @param  string  $salt
     * @return string
     */
    protected function computeHkdf(string $ikm, string $salt): string
    {
        return hash_hkdf('sha256', $ikm, 16, static::INFO_BITS, $salt);
    }
}
