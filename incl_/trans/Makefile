BUCKET = datapinz/MMXXV/polly/
X ?= "frodo"
EXT ?= "ogg"
PREFIX=s3://

stj:
	aws transcribe start-transcription-job \
	--transcription-job-name  $(X) \
	--language-code en-US \
	--media-format $(EXT) \
	--media  MediaFileUri=$(PREFIX)$(BUCKET)$(X).$(EXT) \
	--output-bucket-name  datapinz
	--output-key MMXXV

pushme:
	aws s3 cp $(X).$(EXT) $(PREFIX)$(BUCKET)
