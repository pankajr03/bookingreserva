<?php

namespace BookneticApp\Backend\Customers\DTOs\Response;

class CustomerResponse implements \JsonSerializable
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private ?string $phoneNumber;
    private string $email;
    private ?string $birthdate;
    private ?string $notes;
    private ?string $gender = null;
    private int $userId = 0;
    private ?string $profileImage = null;
    private ?int $categoryId = null;
    private ?string $categoryName = null;

    /**
     * @return int|null
     */
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    /**
     * @var ?int $categoryId
     * @return CustomerResponse
     */
    public function setCategoryId(?int $categoryId): CustomerResponse
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    /**
     * @var ?string $categoryName
     * @return CustomerResponse
     */
    public function setCategoryName(?string $categoryName): CustomerResponse
    {
        $this->categoryName = $categoryName;

        return $this;
    }

    /**
     * @return CustomerResponse
     */
    public static function createEmpty(): CustomerResponse
    {
        $instance = new self();

        $instance->setId(0);
        $instance->setFirstName('');
        $instance->setLastName('');
        $instance->setPhoneNumber('');
        $instance->setEmail('');
        $instance->setBirthdate('');
        $instance->setNotes(null);
        $instance->setGender(null);
        $instance->setUserId(0);
        $instance->setCategoryId(0);
        $instance->setProfileImage('');

        return $instance;
    }

    /**
     * @param int $id
     * @return CustomerResponse
     */
    public function setId(int $id): CustomerResponse
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $firstName
     * @return CustomerResponse
     */
    public function setFirstName(string $firstName): CustomerResponse
    {
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

    public function setLastName(string $lastName): CustomerResponse
    {
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
     * @param string|null $phoneNumber
     * @return CustomerResponse
     */
    public function setPhoneNumber(?string $phoneNumber): CustomerResponse
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $email
     * @return CustomerResponse
     */
    public function setEmail(string $email): CustomerResponse
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
     * @param string $birthdate
     * @return CustomerResponse
     */
    public function setBirthdate(?string $birthdate): CustomerResponse
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
     * @param string|null $notes
     * @return CustomerResponse
     */
    public function setNotes(?string $notes): CustomerResponse
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param ?string $gender
     * @return CustomerResponse
     */
    public function setGender(?string $gender): CustomerResponse
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @param ?string $profileImage
     * @return CustomerResponse
     */
    public function setProfileImage(?string $profileImage): CustomerResponse
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    /**
     * @param int $userId
     * @return CustomerResponse
     */
    public function setUserId(int $userId): CustomerResponse
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
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone_number' => $this->phoneNumber,
            'email' => $this->email,
            'birthdate' => $this->birthdate,
            'notes' => $this->notes,
            'gender' => $this->gender,
            'user_id' => $this->userId,
            'profile_image' => $this->profileImage
        ];
    }
}
