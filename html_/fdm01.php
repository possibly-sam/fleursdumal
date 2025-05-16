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
    <title>AWS Polly Voices</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        .filter-section {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>AWS Polly Voices</h1>

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

    <!-- Voices Table -->
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Gender</th>
                <th>Language Code</th>
                <th>Language Name</th>
                <th>Supported Engines</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($filteredVoices as $voice): ?>
                <tr>
                    <td><?php echo htmlspecialchars($voice['Name']); ?></td>
                    <td><?php echo htmlspecialchars($voice['Gender']); ?></td>
                    <td><?php echo htmlspecialchars($voice['LanguageCode']); ?></td>
                    <td><?php echo htmlspecialchars($voice['LanguageName']); ?></td>
                    <td><?php echo htmlspecialchars(implode(', ', $voice['SupportedEngines'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
