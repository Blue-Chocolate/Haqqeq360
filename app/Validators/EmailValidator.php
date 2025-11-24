<?php 


namespace App\Validators;

class EmailValidator
{
    private const BLOCKED_DOMAINS = [
        'tempmail.com',
        'throwaway.email',
        '10minutemail.com',
        'guerrillamail.com',
        'mailinator.com'
    ];

    /**
     * Validate email format only
     */
    public function validateFormat(string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'reason' => 'invalid_format',
                'message' => 'Invalid email format'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Full email validation (format + domain + blocked check)
     */
    public function validate(string $email): array
    {
        // Check format
        $formatCheck = $this->validateFormat($email);
        if (!$formatCheck['valid']) {
            return $formatCheck;
        }

        // Check blocked domains
        if ($this->isBlockedDomain($email)) {
            return [
                'valid' => false,
                'reason' => 'blocked_domain',
                'message' => 'This email domain is not allowed'
            ];
        }

        // Check if domain exists
        if (!$this->domainExists($email)) {
            return [
                'valid' => false,
                'reason' => 'domain_not_exists',
                'message' => 'Email domain does not exist'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if email domain is blocked
     */
    private function isBlockedDomain(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);
        return in_array(strtolower($domain), self::BLOCKED_DOMAINS);
    }

    /**
     * Check if domain has MX or A records
     */
    private function domainExists(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }
}