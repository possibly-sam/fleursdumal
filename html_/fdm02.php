<?php
// Ensure AWS CLI is configured with proper credentials

// Execute the AWS Polly describe-voices command
$output = shell_exec('aws polly describe-voices');

// Decode the JSON response
$voices = json_decode($output, true);

// Process filters from GET request
$genderFilter = $_GET['gender'] ?? '';
$engineFilter = $_GET['engine'] ?? '';
$languageFilter = $_GET['language'] ?? '';

// Process voice synthesis request
$selectedVoice = $_POST['voice'] ?? '';
$inputText = $_POST['text'] ?? '';
$synthesisResult = '';

if (!empty($selectedVoice) && !empty($inputText)) {
    // Sanitize input
    $safeVoice = escapeshellarg($selectedVoice);
    $inputText = "<speak>" . $inputText . "</speak>";
    $safeText =  escapeshellarg(  htmlspecialchars($inputText) ) ;
    $outputFile = escapeshellarg($selectedVoice . '.ogg');

    // Construct and execute AWS Polly synthesis command
    $command = "aws polly synthesize-speech " .
               "--output-format ogg_vorbis " .
               "--engine neural " .
               "--voice-id $safeVoice " .
               "--text-type ssml " .
               "--text $safeText " .
               "$outputFile 2>&1";

    $synthesisResult = shell_exec($command);
    echo "XXX";
    echo $safeText;
    echo "XXX";
    echo $command;
    echo "XXX<br>";

    // Check if file was created
    if (file_exists($selectedVoice . '.ogg')) {
        $synthesisResult = "Speech synthesized successfully. File saved as $selectedVoice.ogg";
    }
}

// Filter function
function filterVoices($voices, $genderFilter, $engineFilter, $languageFilter) {
    return array_filter($voices['Voices'], function($voice) use ($genderFilter, $engineFilter, $languageFilter) {
        $genderMatch = empty($genderFilter) || $voice['Gender'] === $genderFilter;
        $engineMatch = empty($engineFilter) || in_array($engineFilter, $voice['SupportedEngines']);
        $languageMatch = empty($languageFilter) || $voice['LanguageCode'] === $languageFilter;
        
        return $genderMatch && $engineMatch && $languageMatch;
    });
}

// Apply filters
$filteredVoices = filterVoices($voices, $genderFilter, $engineFilter, $languageFilter);

// Get unique values for filters
$genders = array_unique(array_column($voices['Voices'], 'Gender'));
$engines = array_unique(array_column($voices['Voices'], 'SupportedEngines')[0]);
$languages = array_unique(array_column($voices['Voices'], 'LanguageCode'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>AWS Polly Voice Synthesizer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        .filter-section, .synthesis-section {
            margin-bottom: 20px;
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
        }
        .selected-row {
            background-color: #e0e0e0;
        }
        .synthesis-result {
            margin-top: 15px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>AWS Polly Voice Synthesizer</h1>

    <!-- Filter Form -->
    <div class="filter-section">
        <form method="get">
            <label>Gender:
                <select name="gender">
                    <option value="">All Genders</option>
                    <?php foreach($genders as $gender): ?>
                        <option value="<?php echo htmlspecialchars($gender); ?>" 
                                <?php echo $genderFilter === $gender ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($gender); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Engine:
                <select name="engine">
                    <option value="">All Engines</option>
                    <?php foreach($engines as $engine): ?>
                        <option value="<?php echo htmlspecialchars($engine); ?>" 
                                <?php echo $engineFilter === $engine ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($engine); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Language:
                <select name="language">
                    <option value="">All Languages</option>
                    <?php foreach($languages as $language): ?>
                        <option value="<?php echo htmlspecialchars($language); ?>" 
                                <?php echo $languageFilter === $language ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($language); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <input type="submit" value="Filter">
        </form>
    </div>

    <!-- Voice Synthesis Form -->
    <div class="synthesis-section">
        <form method="post">
            <!-- Text Input -->
            <div>
                <label for="text">Enter Text to Synthesize:</label>
                <textarea id="text" name="text" rows="4" cols="50" required><?php echo htmlspecialchars($inputText); ?></textarea>
            </div>

            <!-- Voices Table -->
            <table>
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Language Code</th>
                        <th>Language Name</th>
                        <th>Supported Engines</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($filteredVoices as $voice): ?>
                        <tr class="<?php echo $selectedVoice === $voice['Id'] ? 'selected-row' : ''; ?>">
                            <td>
                                <input type="radio" name="voice" value="<?php echo htmlspecialchars($voice['Id']); ?>" 
                                       <?php echo $selectedVoice === $voice['Id'] ? 'checked' : ''; ?> required>
                            </td>
                            <td><?php echo htmlspecialchars($voice['Name']); ?></td>
                            <td><?php echo htmlspecialchars($voice['Gender']); ?></td>
                            <td><?php echo htmlspecialchars($voice['LanguageCode']); ?></td>
                            <td><?php echo htmlspecialchars($voice['LanguageName']); ?></td>
                            <td><?php echo htmlspecialchars(implode(', ', $voice['SupportedEngines'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Synthesize Button -->
            <div style="margin-top: 15px;">
                <input type="submit" value="Synthesize Speech">
            </div>
        </form>

        <!-- Synthesis Result -->
        <?php if (!empty($synthesisResult)): ?>
            <div class="synthesis-result">
                <h3>Synthesis Result:</h3>
                <pre><?php echo htmlspecialchars($synthesisResult); ?></pre>
                
                <?php if (file_exists($selectedVoice . '.ogg')): ?>
                    <audio controls>
                        <source src="<?php echo htmlspecialchars($selectedVoice . '.ogg'); ?>" type="audio/ogg">
                        Your browser does not support the audio element.
                    </audio>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
