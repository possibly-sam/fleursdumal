

# More robust version that handles potential null values
jq -r ' .[] | .Transcript.Results[]?.Alternatives[]?.Transcript // empty' $1

