<?php

namespace App\Services;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;

class MikrotikService
{
    protected $client;
    protected $router;

    public function __construct(?Router $router = null)
    {
        $this->router = $router ?? Router::active();
    }

    /**
     * Connect to the Mikrotik Router
     */
    public function connect()
    {
        if (!$this->router) {
            \Log::error('Mikrotik Connection Error: No active router found.');
            return false;
        }

        try {
            $host = Router::normalizeHost($this->router->ip_address);
            $port = (int) $this->router->port;

            $this->client = new Client([
                'host' => $host,
                'user' => $this->router->username,
                'pass' => $this->router->password,
                'port' => $port,
                'socket_timeout' => 10, // Reduced for faster fail
                'timeout' => 3, // Very fast connection timeout
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Mikrotik Connection Error: ' . $e->getMessage(), [
                'router_id' => $this->router->id,
                'host' => Router::normalizeHost($this->router->ip_address),
                'port' => (int) $this->router->port,
                'username' => $this->router->username,
            ]);
            return false;
        }
    }

    /**
     * Get all active interfaces from MikroTik
     */
    public function getInterfaces()
    {
        if (!$this->client) {
            $this->connect();
        }

        if (!$this->client) {
            return [];
        }

        try {
            $query = (new Query('/interface/print'))
                ->where('disabled', 'false')
                ->where('running', 'true');
                
            $interfaces = $this->client->query($query)->read();
            return $interfaces;
        } catch (\Exception $e) {
            \Log::error('MikroTik Get Interfaces Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get real-time traffic for specific interfaces
     */
    public function getTraffic(array $interfaces)
    {
        if (empty($interfaces)) return [];
        
        if (!$this->client) {
            $this->connect();
        }

        if (!$this->client) {
            return [];
        }

        try {
            $trafficData = [];

            foreach ($interfaces as $interfaceName) {
                $interfaceName = trim((string) $interfaceName);

                if ($interfaceName === '') {
                    continue;
                }

                $query = (new Query('/interface/monitor-traffic'))
                    ->equal('interface', $interfaceName)
                    ->equal('once', '');

                $result = $this->client->query($query)->read();
                $trap = $this->extractTrapMessage($result);

                if ($trap) {
                    \Log::warning('MikroTik Traffic Interface Error', [
                        'interface' => $interfaceName,
                        'message' => $trap,
                    ]);
                    $trafficData[] = [
                        'name' => $interfaceName,
                        'rx-bits-per-second' => 0,
                        'tx-bits-per-second' => 0,
                        'error' => $trap,
                    ];
                    continue;
                }

                $row = $result[0] ?? $result;

                if (is_array($row)) {
                    $row['name'] = $row['name'] ?? $row['interface'] ?? $interfaceName;
                    $row['rx-bits-per-second'] = $this->parseRouterOsBits($row['rx-bits-per-second'] ?? 0);
                    $row['tx-bits-per-second'] = $this->parseRouterOsBits($row['tx-bits-per-second'] ?? 0);
                    $trafficData[] = $row;
                }
            }

            return $trafficData;
        } catch (\Exception $e) {
            \Log::error('MikroTik Get Traffic Error: ' . $e->getMessage());
            return [];
        }
    }

    private function extractTrapMessage($result): ?string
    {
        if (!is_array($result)) {
            return null;
        }

        if (isset($result['!trap'])) {
            return $result['!trap'][0]['message'] ?? 'RouterOS menolak request traffic.';
        }

        if (isset($result[0]['!trap'])) {
            return $result[0]['!trap'][0]['message'] ?? $result[0]['message'] ?? 'RouterOS menolak request traffic.';
        }

        return null;
    }

    private function parseRouterOsBits($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $value = trim(strtolower((string) $value));

        if ($value === '') {
            return 0;
        }

        if (!preg_match('/([\d.]+)\s*([kmgt]?)(?:bps|b\/s)?/', $value, $matches)) {
            return (int) $value;
        }

        $number = (float) $matches[1];
        $unit = $matches[2] ?? '';
        $multiplier = match ($unit) {
            'k' => 1000,
            'm' => 1000000,
            'g' => 1000000000,
            't' => 1000000000000,
            default => 1,
        };

        return (int) round($number * $multiplier);
    }

    /**
     * Get system resource
     */
    public function getSystemResource()
    {
        if (!$this->client) {
            $this->connect();
        }

        if (!$this->client) {
            return [];
        }

        $query = new Query('/system/resource/print');
        return $this->client->query($query)->read();
    }

    public function getActiveConnections(array $usernames = [])
    {
        if (!$this->client) {
            $this->connect();
        }

        if (!$this->client) {
            return [];
        }

        if (empty($usernames)) {
            $query = new Query('/ppp/active/print');
            return $this->client->query($query)->read();
        }

        $results = [];
        // RouterOS API engine crashes on large OR expressions. Max safe size is 5.
        $chunks = array_chunk($usernames, 5); 
        
        foreach ($chunks as $chunk) {
            $query = new Query('/ppp/active/print');
            $first = true;
            foreach ($chunk as $name) {
                $query->where('name', trim($name));
                if (!$first) {
                    // Push OR immediately to keep internal RPN stack depth at 2
                    $query->add('?#|');
                }
                $first = false;
            }
            
            $res = $this->client->query($query)->read();
            if (is_array($res) && !isset($res['!trap'])) {
                $results = array_merge($results, $res);
            }
        }
        
        return $results;
    }

    /**
     * Get PPPoE statistics (Active, Offline, Disabled, Isolir)
     */
    public function getPppoeStats()
    {
        if (!$this->client) {
            $this->connect();
        }

        if (!$this->client) {
            return [
                'active' => 0,
                'offline' => 0,
                'disabled' => 0,
                'isolir' => 0,
                'total' => 0
            ];
        }

        $stats = [
            'active' => 0,
            'offline' => 0,
            'disabled' => 0,
            'isolir' => 0,
            'total' => 0
        ];

        try {
            // Get all secrets
            $qSecrets = new Query('/ppp/secret/print');
            $secrets = $this->client->query($qSecrets)->read();
            
            // Get active connections
            $qActive = new Query('/ppp/active/print');
            $active = $this->client->query($qActive)->read();

            $stats['total'] = count($secrets);
            $stats['active'] = count($active);
            
            foreach ($secrets as $secret) {
                // Check if disabled
                if (isset($secret['disabled']) && $secret['disabled'] === 'true') {
                    $stats['disabled']++;
                }
                
                // Check if isolir
                if (isset($secret['profile']) && strtolower($secret['profile']) === 'isolir') {
                    $stats['isolir']++;
                }
            }

            // Offline is total - active
            $stats['offline'] = max(0, $stats['total'] - $stats['active']);

        } catch (\Exception $e) {
            \Log::error('MikroTik PPPoE Stats Error: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get all PPPoE Secrets
     */
    public function getPppoeSecrets()
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            $query = new Query('/ppp/secret/print');
            return $this->client->query($query)->read();
        } catch (\Exception $e) {
            \Log::error('MikroTik Get Secrets Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all PPPoE Profiles
     */
    public function getPppoeProfiles()
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            $query = new Query('/ppp/profile/print');
            return $this->client->query($query)->read();
        } catch (\Exception $e) {
            \Log::error('MikroTik Get Profiles Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Add new PPPoE Profile
     */
    public function addPppoeProfile($name, $rateLimit = null)
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            $query = (new Query('/ppp/profile/add'))
                ->equal('name', $name);
                
            if ($rateLimit) {
                $query->equal('rate-limit', $rateLimit);
            }
            
            $res = $this->client->query($query)->read();
            
            if (isset($res['!trap'])) {
                $errMsg = $res['!trap'][0]['message'] ?? 'Unknown error';
                throw new \Exception($errMsg);
            }
            return true;
        } catch (\Exception $e) {
            \Log::error('MikroTik Add Profile Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update PPPoE Profile
     */
    public function updatePppoeProfile($oldName, $newName, $rateLimit = null)
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            // First find the profile ID
            $queryFind = (new Query('/ppp/profile/print'))->where('name', $oldName);
            $profiles = $this->client->query($queryFind)->read();
            
            if (empty($profiles)) return false;
            
            $id = $profiles[0]['.id'];
            
            $query = (new Query('/ppp/profile/set'))
                ->equal('.id', $id)
                ->equal('name', $newName);
                
            if ($rateLimit) {
                $query->equal('rate-limit', $rateLimit);
            }
            
            $res = $this->client->query($query)->read();
            if (isset($res['!trap'])) {
                $errMsg = $res['!trap'][0]['message'] ?? 'Unknown error';
                throw new \Exception($errMsg);
            }
            return true;
        } catch (\Exception $e) {
            \Log::error('MikroTik Update Profile Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete PPPoE Profile
     */
    public function deletePppoeProfile($name)
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            $queryFind = (new Query('/ppp/profile/print'))->where('name', $name);
            $profiles = $this->client->query($queryFind)->read();
            
            if (empty($profiles)) return true;
            
            $id = $profiles[0]['.id'];
            $query = (new Query('/ppp/profile/remove'))->equal('.id', $id);
            $this->client->query($query)->read();
            return true;
        } catch (\Exception $e) {
            \Log::error('MikroTik Delete Profile Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add new PPPoE Secret
     */
    public function addPppoeSecret($name, $password, $profile = 'default', $service = 'pppoe')
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            $query = (new Query('/ppp/secret/add'))
                ->equal('name', $name)
                ->equal('password', $password)
                ->equal('profile', $profile)
                ->equal('service', $service);
            
            $res = $this->client->query($query)->read();

            // Check for API errors
            if (isset($res['after']['message'])) {
                throw new \Exception($res['after']['message']);
            }
            if (isset($res['!trap'])) {
                $errMsg = $res['!trap'][0]['message'] ?? 'Unknown error';
                throw new \Exception($errMsg);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('MikroTik Add Secret Error: ' . $e->getMessage());
            throw $e; // Re-throw to be caught by controller
        }
    }

    /**
     * Update PPPoE Secret
     */
    public function updatePppoeSecret($oldName, $newName, $password, $profile = 'default', $service = 'pppoe')
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            // Find the ID first
            $queryFind = (new Query('/ppp/secret/print'))
                ->where('name', $oldName);
            $secrets = $this->client->query($queryFind)->read();
            
            if (isset($secrets['!trap']) || (isset($secrets[0]) && isset($secrets[0]['!trap']))) {
                throw new \Exception("MikroTik API Error when finding secret.");
            }
            
            if (empty($secrets)) {
                // If it doesn't exist, create it instead of throwing an error!
                return $this->addPppoeSecret($newName, $password, $profile, $service);
            }
            
            $id = $secrets[0]['.id'];
            
            $query = (new Query('/ppp/secret/set'))
                ->equal('.id', $id)
                ->equal('name', $newName)
                ->equal('password', $password)
                ->equal('profile', $profile)
                ->equal('service', $service);
            
            $res = $this->client->query($query)->read();

            if (isset($res['!trap'])) {
                $errMsg = $res['!trap'][0]['message'] ?? 'Unknown error';
                throw new \Exception($errMsg);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('MikroTik Update Secret Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function togglePppoeSecret($name, $disable)
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            // Find the ID first
            $queryFind = (new Query('/ppp/secret/print'))
                ->where('name', $name);
            $secrets = $this->client->query($queryFind)->read();
            
            if (isset($secrets['!trap']) || (isset($secrets[0]) && isset($secrets[0]['!trap']))) {
                throw new \Exception("MikroTik API Error when finding secret.");
            }
            
            if (empty($secrets)) {
                throw new \Exception("Akun '$name' tidak ditemukan di MikroTik.");
            }
            
            $id = $secrets[0]['.id'];
            
            $cmd = $disable ? '/ppp/secret/disable' : '/ppp/secret/enable';
            $query = (new Query($cmd))
                ->equal('.id', $id);
            
            $res = $this->client->query($query)->read();

            if (isset($res['!trap'])) {
                $errMsg = $res['!trap'][0]['message'] ?? 'Unknown error';
                throw new \Exception($errMsg);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('MikroTik Toggle Secret Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete PPPoE Secret
     */
    public function deletePppoeSecret($name)
    {
        if (!$this->client) {
            $this->connect();
        }

        try {
            // Find the ID first
            $query = (new Query('/ppp/secret/print'))
                ->where('name', $name);
            $secrets = $this->client->query($query)->read();
            
            if (isset($secrets['!trap']) || (isset($secrets[0]) && isset($secrets[0]['!trap']))) {
                return false;
            }

            if (empty($secrets)) {
                // Already deleted or doesn't exist, consider it a success
                return true;
            }

            if (isset($secrets[0]['.id'])) {
                $removeQuery = (new Query('/ppp/secret/remove'))
                    ->equal('.id', $secrets[0]['.id']);
                $this->client->query($removeQuery)->read();
                return true;
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('MikroTik Delete Secret Error: ' . $e->getMessage());
            return false;
        }
    }
}
