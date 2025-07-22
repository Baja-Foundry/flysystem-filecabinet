<?php

require_once 'vendor/autoload.php';

use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;

// Configuration - replace with your actual NetSuite credentials
$config = [
    'base_url' => 'https://YOUR_ACCOUNT_ID.suitetalk.api.netsuite.com',
    'consumer_key' => 'YOUR_CONSUMER_KEY',
    'consumer_secret' => 'YOUR_CONSUMER_SECRET',
    'token_id' => 'YOUR_TOKEN_ID',
    'token_secret' => 'YOUR_TOKEN_SECRET',
    'realm' => 'YOUR_ACCOUNT_ID',
    'timeout' => 30,
];

echo "Testing NetSuite FileCabinet Connection...\n";
echo "=====================================\n\n";

try {
    $client = new NetSuiteClient($config);
    $result = $client->testConnection();
    
    if ($result['success']) {
        echo "✅ SUCCESS: " . $result['message'] . "\n";
        
        if (isset($result['data']['items']) && !empty($result['data']['items'])) {
            echo "📁 Root folder found:\n";
            foreach ($result['data']['items'] as $folder) {
                echo "   - ID: " . ($folder['id'] ?? 'N/A') . "\n";
                echo "   - Name: " . ($folder['name'] ?? 'N/A') . "\n";
            }
        }
        
        echo "\n🎉 Your NetSuite FileCabinet connection is working properly!\n";
    } else {
        echo "❌ FAILED: " . $result['message'] . "\n";
        echo "Error Details: " . ($result['error'] ?? 'Unknown error') . "\n";
        
        echo "\n🔧 Troubleshooting Tips:\n";
        echo "1. Verify your NetSuite account ID in base_url and realm\n";
        echo "2. Check your OAuth credentials (consumer key/secret, token ID/secret)\n";
        echo "3. Ensure your integration has proper permissions for FileCabinet access\n";
        echo "4. Verify the SuiteQL role has query permissions\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "\n🔧 This usually indicates a configuration or network issue.\n";
}

echo "\n📚 For more information, see NetSuite's REST API documentation:\n";
echo "https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/chapter_1540391670.html\n";