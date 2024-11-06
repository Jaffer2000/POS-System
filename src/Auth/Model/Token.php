<?php

namespace Thirtybees\Module\POS\Auth\Model;



use DateTime;
use Db;
use DbQuery;
use PrestaShopException;
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
    private int $orderProcessId;

    /**
     * @var Employee | null
     */
    private $employee = null;


    /**
     * @param int $id
     * @param string $value
     * @param int $employeeId
     * @param string $role
     * @param int $orderProcessId
     * @param DateTime $generated
     * @param DateTime $expiration
     */
    public function __construct(
        int      $id,
        string   $value,
        int      $employeeId,
        string   $role,
        int      $orderProcessId,
        DateTime $generated,
        DateTime $expiration
    ) {
        $this->id = $id;
        $this->value = $value;
        $this->employeeId = $employeeId;
        $this->generated = $generated;
        $this->expiration = $expiration;
        $this->role = $role;
        $this->orderProcessId = $orderProcessId;
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
     *
     * @throws PrestaShopException
     */
    public function getEmployee(): Employee
    {
        if (is_null($this->employee)) {
            $employee = new Employee($this->employeeId);
            if (Validate::isLoadedObject($employee)) {
                $this->employee = $employee;
            } else {
                throw new ServerErrorException("Employee with id " . $this->employeeId . " not found");
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
    public function getOrderProcessId(): int
    {
        return (int)$this->orderProcessId;
    }

    /**
     * @param int $orderProcessId
     *
     * @return Token
     */
    public function setOrderProcessId(int $orderProcessId): Token
    {
        $this->orderProcessId = (int)$orderProcessId;
        return $this;
    }


    /**
     * @param DateTime $exp
     * @return $this
     */
    public function setExpiration(DateTime $exp): Token
    {
        $this->expiration = $exp;
        return $this;
    }


}