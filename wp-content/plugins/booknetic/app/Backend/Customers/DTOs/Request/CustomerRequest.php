<?php

namespace BookneticApp\Backend\Customers\DTOs\Request;

use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerDataException;

class CustomerRequest
{
    private string $firstName;
    private string $lastName = '';
    private string $phoneNumber;
    private string $email;
    private ?string $birthdate;
    private string $notes;
    private string $gender;
    private int $userId;
    private ?string $profileImage = null;
    private string $createdBy;
    private string $createdAt;
    private int $categoryId = 0;
    private string $wpUserPassword;

    /**
     * @param string $firstName
     * @return CustomerRequest
     * @throws InvalidCustomerDataException
     */
    public function setFirstName(string $firstName): CustomerRequest
    {
        if (empty($firstName)) {
            throw new InvalidCustomerDataException(bkntc__('The first name field is required!'));
        }

        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $lastName
     * @return CustomerRequest
     * @throws InvalidCustomerDataException
     */
    public function setLastName(string $lastName): CustomerRequest
    {
        if (empty($lastName)) {
            throw new InvalidCustomerDataException(bkntc__('The last name field is required!'));
        }

        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $phoneNumber
     * @return CustomerRequest
     */
    public function setPhoneNumber(string $phoneNumber): CustomerRequest
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $email
     * @return CustomerRequest
     */
    public function setEmail(string $email): CustomerRequest
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string|null $birthdate
     * @return CustomerRequest
     */
    public function setBirthdate(?string $birthdate): CustomerRequest
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBirthdate(): ?string
    {
        return $this->birthdate;
    }

    /**
     * @param string $notes
     * @return CustomerRequest
     */
    public function setNotes(string $notes): CustomerRequest
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string $gender
     * @return CustomerRequest
     */
    public function setGender(string $gender): CustomerRequest
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return string
     */
    public function getGender(): string
    {
        return $this->gender;
    }

    /**
     * @param ?string $profileImage
     * @return CustomerRequest
     */
    public function setProfileImage(?string $profileImage): CustomerRequest
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    /**
     * @param int $userId
     * @return CustomerRequest
     */
    public function setUserId(int $userId): CustomerRequest
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param string $createdBy
     */
    public function setCreatedBy(string $createdBy): CustomerRequest
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    /**
     * @param string $createdAt
     * @return CustomerRequest
     */
    public function setCreatedAt(string $createdAt): CustomerRequest
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    /**
     * @param int|null $categoryId
     * @return CustomerRequest
     */
    public function setCategoryId(int $categoryId): CustomerRequest
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function setWpUserPassword(string $wpUserPassword): CustomerRequest
    {
        $this->wpUserPassword = $wpUserPassword;

        return $this;
    }

    public function getWpUserPassword(): string
    {
        return $this->wpUserPassword;
    }
}
