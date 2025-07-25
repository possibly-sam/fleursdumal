# Extract just the transcript text from AWS Transcribe JSON
jq -r '.Transcript.Results[].Alternatives[].Transcript' input.json

# If you want to concatenate all transcript segments into one continuous text
jq -r '.Transcript.Results[].Alternatives[].Transcript' input.json | tr '\n' ' '

# Alternative: Join all transcripts with spaces in a single jq command
jq -r '[.Transcript.Results[].Alternatives[].Transcript] | join(" ")' input.json

# If you want each transcript on a separate line (default behavior)
jq -r '.Transcript.Results[].Alternatives[].Transcript' input.json

# More robust version that handles potential null values
jq -r '.Transcript.Results[]?.Alternatives[]?.Transcript // empty' input.json

# If you want to extract and clean up (remove extra spaces)
jq -r '[.Transcript.Results[].Alternatives[].Transcript] | join(" ")' input.json | sed 's/  */ /g'