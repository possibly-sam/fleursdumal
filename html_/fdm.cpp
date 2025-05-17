/*
 * fdm.cpp
 * 
 * Copyright 2025 pma <pma@salacia>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */

#include <iostream>
#include <fstream>
#include <sstream>
#include <string>
#include <vector>
#include <cstdlib>

class PollySpeechSynthesizer {
private:
    std::string filename;
    std::string voiceName;
    std::string fileContents;
    std::string ssmlText;

    // Read file contents into a string
    bool readFile() {
        std::ifstream file(filename);
        if (!file.is_open()) {
            std::cerr << "Error: Could not open file " << filename << std::endl;
            return false;
        }

        std::stringstream buffer;
        buffer << file.rdbuf();
        fileContents = buffer.str();
        return true;
    }

    // Convert text to SSML with pauses
    void convertToSSML() {
        std::istringstream iss(fileContents);
        std::string line;
        std::vector<std::string> lines;

        // Read lines
        while (std::getline(iss, line)) {
            lines.push_back(line);
        }

        // Build SSML
        ssmlText = "<speak><prosody rate=\"slow\">\n";
        for (const auto& l : lines) {
            ssmlText += l + "<p/>\n";
        }
        ssmlText += "</prosody></speak>";
    }

    // Build and execute AWS Polly command
    void executePollyCommand() {
        // Escape quotes in the SSML text
        std::string escapedText;
        for (char c : ssmlText) {
            if (c == '"') {
                escapedText += "\\\"";
            } else {
                escapedText += c;
            }
        }

        // Construct AWS Polly command
        std::string command = "aws polly synthesize-speech "
                              "--output-format ogg_vorbis "
                              "--engine neural "
                              "--voice-id " + voiceName + " "
                              "--text-type ssml "
                              "--text \"" + escapedText + "\" " +
                              voiceName + ".ogg";

        std::cout << "Executing command: " << command << std::endl;

        // Execute command
        int result = system(command.c_str());

        if (result == 0) {
            std::cout << "Speech synthesis successful. Output saved as " 
                      << voiceName << ".ogg" << std::endl;
        } else {
            std::cerr << "Error executing AWS Polly command" << std::endl;
        }
    }

public:
    PollySpeechSynthesizer(const std::string& fname, const std::string& voice)
        : filename(fname), voiceName(voice) {}

    bool process() {
        if (!readFile()) return false;
        convertToSSML();
        executePollyCommand();
        return true;
    }
};

int main(int argc, char* argv[]) {
    // Check for correct number of arguments
    if (argc != 3) {
        std::cerr << "Usage: " << argv[0] << " <filename> <voice-name>" << std::endl;
        return 1;
    }

    std::string filename = argv[1];
    std::string voiceName = argv[2];

    PollySpeechSynthesizer synthesizer(filename, voiceName);
    
    if (!synthesizer.process()) {
        return 1;
    }

    return 0;
}
