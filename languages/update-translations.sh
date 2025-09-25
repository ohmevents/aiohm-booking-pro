#!/bin/bash

# AIOHM Booking Pro - Translation Update Script
# This script helps maintain and update translation files

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}AIOHM Booking Pro - Translation Update Script${NC}"
echo "=================================================="

# Function to check if msgfmt is available
check_msgfmt() {
    if ! command -v msgfmt &> /dev/null; then
        echo -e "${RED}Error: msgfmt not found. Please install gettext tools.${NC}"
        echo "Ubuntu/Debian: sudo apt-get install gettext"
        echo "macOS: brew install gettext"
        exit 1
    fi
}

# Function to extract strings from source code
extract_strings() {
    echo -e "${YELLOW}Extracting translatable strings from source code...${NC}"
    
    if command -v xgettext &> /dev/null; then
        find "$PLUGIN_DIR" -name "*.php" -not -path "*/vendor/*" -not -path "*/node_modules/*" | \
        xgettext --files-from=- \
                 --language=PHP \
                 --keyword=__ \
                 --keyword=_e \
                 --keyword=esc_html__ \
                 --keyword=esc_attr__ \
                 --keyword=_n:1,2 \
                 --keyword=_nx:1,2,4c \
                 --keyword=_x:1,2c \
                 --keyword=esc_html_x:1,2c \
                 --from-code=UTF-8 \
                 --package-name="AIOHM Booking Pro" \
                 --package-version="2.0.3" \
                 --msgid-bugs-address="https://wordpress.org/support/plugin/aiohm-booking-pro" \
                 --output="$SCRIPT_DIR/aiohm-booking-pro.pot"
        
        echo -e "${GREEN}✅ Updated template file: aiohm-booking-pro.pot${NC}"
    else
        echo -e "${YELLOW}⚠️  xgettext not found. Using existing template.${NC}"
    fi
}

# Function to compile .po to .mo
compile_po() {
    local po_file="$1"
    local mo_file="${po_file%.po}.mo"
    
    if [ -f "$po_file" ]; then
        echo -e "${BLUE}Compiling: $(basename "$po_file")${NC}"
        if msgfmt -o "$mo_file" "$po_file" 2>/dev/null; then
            echo -e "${GREEN}✅ Compiled: $(basename "$mo_file")${NC}"
        else
            echo -e "${RED}❌ Error compiling: $(basename "$po_file")${NC}"
        fi
    fi
}

# Function to validate .po file
validate_po() {
    local po_file="$1"
    
    if [ -f "$po_file" ]; then
        echo -e "${BLUE}Validating: $(basename "$po_file")${NC}"
        if msgfmt -c "$po_file" 2>/dev/null; then
            echo -e "${GREEN}✅ Valid: $(basename "$po_file")${NC}"
        else
            echo -e "${RED}❌ Validation failed: $(basename "$po_file")${NC}"
            msgfmt -c "$po_file"
        fi
    fi
}

# Function to show statistics
show_stats() {
    local po_file="$1"
    
    if [ -f "$po_file" ]; then
        local stats=$(msgfmt --statistics "$po_file" 2>&1)
        echo -e "${BLUE}Stats for $(basename "$po_file"): ${NC}$stats"
    fi
}

# Main execution
main() {
    cd "$SCRIPT_DIR"
    
    # Check dependencies
    check_msgfmt
    
    # Extract strings if requested
    if [[ "$1" == "--extract" || "$1" == "-e" ]]; then
        extract_strings
    fi
    
    echo -e "\n${YELLOW}Processing translation files...${NC}"
    
    # Find all .po files and process them
    for po_file in *.po; do
        if [ -f "$po_file" ]; then
            echo -e "\n${BLUE}Processing: $po_file${NC}"
            validate_po "$po_file"
            compile_po "$po_file"
            show_stats "$po_file"
        fi
    done
    
    echo -e "\n${GREEN}Translation update completed!${NC}"
    
    # List all available translation files
    echo -e "\n${BLUE}Available translations:${NC}"
    for mo_file in *.mo; do
        if [ -f "$mo_file" ]; then
            local size=$(du -h "$mo_file" | cut -f1)
            echo -e "${GREEN}✅ $mo_file ($size)${NC}"
        fi
    done
    
    # Show help if no .po files found
    if ! ls *.po 1> /dev/null 2>&1; then
        echo -e "\n${YELLOW}No .po files found. To create a new translation:${NC}"
        echo "1. Copy the template: cp aiohm-booking-pro.pot aiohm-booking-pro-[locale].po"
        echo "2. Edit the .po file with your translations"
        echo "3. Run this script to compile"
    fi
}

# Help function
show_help() {
    echo "AIOHM Booking Pro - Translation Update Script"
    echo ""
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  -e, --extract    Extract strings from source code first"
    echo "  -h, --help       Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0               Compile all existing .po files"
    echo "  $0 --extract     Extract strings and compile all .po files"
}

# Handle command line arguments
case "$1" in
    -h|--help)
        show_help
        exit 0
        ;;
    *)
        main "$@"
        ;;
esac