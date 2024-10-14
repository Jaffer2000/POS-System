<?php

namespace Thirtybees\Module\POS\Auth\Model;



use DateTime;
use Db;
use DbQuery;
use PrestaShopException;
use Thirtybees\Module\POS\Exception\InvalidArgumentException;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Exception\ServerErrorException;
use Tools;
use Employee;
use Validate;

class Token
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $employeeId;

    /**
     * @var DateTime
     */
    private $generated;

    /**
     * @var DateTime
     */
    private $expiration;

    /**
     * @var String
     */
    private string $role;

    /**
     * @var int
     */
    private int $cartId;

    /**
     * @var Employee | null
     */
    private $employee = null;


    /**
     * @param int $id
     * @param string $value
     * @param int $employeeId
     * @param string $role
     * @param int $cartId
     * @param DateTime $generated
     * @param DateTime $expiration
     */
    protected function __construct(
        int $id,
        string $value,
        int $employeeId,
        string $role,
        int $cartId,
        DateTime $generated,
        DateTime $expiration
    ) {
        $this->id = $id;
        $this->value = $value;
        $this->employeeId = $employeeId;
        $this->generated = $generated;
        $this->expiration = $expiration;
        $this->role = $role;
        $this->cartId = $cartId;
    }

    /**
     * @param string $headerValue
     * @return Token | false
     * @throws PrestaShopException
     */
    public static function getFromAuthHeader(string $headerValue)
    {
        if (!$headerValue) {
            return false;
        }
        $tokenValue = preg_replace("/^Bearer +/i", "", $headerValue);
        if (!$tokenValue) {
            return false;
        }

        if (!preg_match("/^[a-zA-Z0-9]{32}$/", $tokenValue)) {
            return false;
        }

        return static::getFromValue($tokenValue);
    }

    /**
     * @param string $value
     * @return Token | false
     *
     * @throws PrestaShopException
     */
    public static function getFromValue(string $value)
    {
        $sql = (new DbQuery)
            ->select('t.*')
            ->from('tbpos_token', 't')
            ->innerJoin('employee', 'e', '(e.id_employee = t.id_employee)')
            ->where('t.value = "' . pSQL($value) . '"')
            ->where('e.active')
            ->where('t.expiration > UNIX_TIMESTAMP()');
        $row = Db::getInstance()->getRow($sql);
        if ($row === false) {
            return false;
        }
        return new Token(
            (int)$row['id_tbpos_token'],
            $value,
            (int)$row['id_employee'],
            (string)$row['role'],
            (int)$row['id_cart'],
            static::getDateTime((int)$row['generated']),
            static::getDateTime((int)$row['expiration'])
        );
    }

    /**
     * @param int $employeeId
     * @param string $role
     * @param int $cartId
     * @param int $ttl
     *
     * @return Token
     *
     * @throws PrestaShopException
     */
    public static function generateToken(int $employeeId, string $role, int $cartId = 0, int $ttl = 3600)
    {
        if (! Role::isValidRole($role)) {
            throw new InvalidArgumentException('Invalid role: ' . $role);
        }
        $conn = Db::getInstance();
        $generated = time();
        $expiration = $generated + $ttl;

        $value = static::generateTokenValue();
        $result = $conn->insert('tbpos_token', [
            'id_employee' => (int)$employeeId,
            'value' => pSQL($value),
            'role' => pSQL($role),
            'id_cart' => (int)$cartId,
            'generated' => $generated,
            'expiration' => $expiration,
        ]);

        if (! $result) {
            throw new ServerErrorException("Failed to generate token");
        }

        return new static(
            (int)$conn->Insert_ID(),
            $value,
            $employeeId,
            $role,
            $cartId,
            static::getDateTime($generated),
            static::getDateTime($expiration)
        );
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    protected static function generateTokenValue()
    {
        while (true) {
            $value = Tools::passwdGen(32);
            $sql = (new DbQuery())
                ->select('1')
                ->from('tbpos_token')
                ->where('value = "' . pSQL($value) . '"');
            if (Db::getInstance()->getValue($sql) === false) {
                return $value;
            }
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    /**
     * @return Employee
     * @throws PrestaShopException
     * @throws NotFoundException
     */
    public function getEmployee(): Employee
    {
        if (is_null($this->employee)) {
            $employee = new Employee($this->employeeId);
            if (Validate::isLoadedObject($employee)) {
                $this->employee = $employee;
            } else {
                throw new NotFoundException("Employee with id " . $this->employeeId . " not found");
            }
        }
        return $this->employee;
    }



        /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return DateTime
     */
    public function getGenerated(): DateTime
    {
        return $this->generated;
    }

    /**
     * @return DateTime
     */
    public function getExpiration(): DateTime
    {
        return $this->expiration;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int
    {
        $now = new DateTime();
        return max(0, $this->getExpiration()->getTimestamp() - $now->getTimestamp());
    }

    /**
     * @return boolean
     * @throws PrestaShopException
     */
    public function revoke()
    {
        return Db::getInstance()->delete('tbpos_token', 'value = "' . pSQL($this->value) . '"');
    }

    /**
     * @param int $ts
     * @return DateTime
     */
    private static function getDateTime(int $ts)
    {
        $datetime = new DateTime();
        $datetime->setTimestamp($ts);
        return $datetime;
    }

    /**
     * @return string
     *
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @return int
     */
    public function getCartId(): int
    {
        return (int)$this->cartId;
    }

    /**
     * @param int $cartId
     *
     * @return Token
     *
     * @throws PrestaShopException
     */
    public function updateCartId(int $cartId): Token
    {
        $this->cartId = (int)$cartId;
        Db::getInstance()->update('tbpos_token', [
            'id_cart' => $this->getCartId()
        ], 'id_tbpos_token = ' . $this->getId());
        return $this;
    }


}