# AIOHM Booking Pro - AI Features Documentation

## Overview

AIOHM Booking Pro includes a comprehensive suite of AI-powered features that enhance the booking experience through intelligent automation, advanced analytics, and smart integrations. All AI features are premium-only and require a valid Pro license.

## AI Provider Modules

### 1. OpenAI Integration (`openai`)

**Description**: Industry-leading AI with advanced language understanding and natural conversation capabilities.

**Features**:
- **GPT Models Support**: GPT-3.5 Turbo, GPT-4, GPT-4 Turbo
- **Configurable Parameters**:
  - API Key configuration
  - Model selection (GPT-3.5 Turbo, GPT-4, GPT-4 Turbo)
  - Max tokens control (100-4000)
  - Temperature settings (0.0-1.0) for creativity control
- **Booking Intelligence**: AI-powered booking insights and analytics
- **Natural Language Processing**: Advanced language understanding for customer queries
- **Connection Testing**: Built-in API connection validation

**Settings**:
- `openai_api_key` (Password field): Your OpenAI API key
- `openai_model` (Select): Choose between available models
- `openai_max_tokens` (Number): Maximum response length
- `openai_temperature` (Number): Creativity level

**Access Level**: Premium only (`premium`)

---

### 2. Google Gemini Integration (`gemini`)

**Description**: Google's most capable AI model with multimodal understanding and advanced reasoning capabilities.

**Features**:
- **Gemini Models Support**:
  - Gemini Pro (Text & Reasoning)
  - Gemini Pro Vision (Text & Images)
  - Gemini Ultra (Most Capable)
- **Multimodal Capabilities**: Support for both text and image processing
- **Advanced Reasoning**: Enhanced logical processing for complex queries
- **Configurable Parameters**:
  - API Key management
  - Model selection
  - Temperature control (0.0-1.0)
  - Max output tokens (100-8000)

**Settings**:
- `gemini_api_key` (Password field): Google AI API key
- `gemini_model` (Select): Choose Gemini model variant
- `gemini_temperature` (Number): Creativity level
- `gemini_max_tokens` (Number): Maximum tokens for responses

**Access Level**: Premium only (`premium`)

---

### 3. ShareAI Integration (`shareai`)

**Description**: Custom AI provider integration for shared AI resources and collaborative intelligence.

**Features**:
- **Shared AI Resources**: Access to collaborative AI capabilities
- **Custom Integration**: Tailored for AIOHM Booking specific use cases
- **Cost-Effective**: Optimized for frequent usage scenarios
- **Collaborative Intelligence**: Shared learning across booking instances

**Access Level**: Premium only (`premium`)

---

### 4. Ollama Integration (`ollama`)

**Description**: Private, self-hosted AI solution for maximum privacy and control.

**Features**:
- **Self-Hosted**: Complete control over AI processing
- **Privacy First**: All data stays on your infrastructure
- **Local Processing**: No external API calls required
- **Custom Models**: Support for custom trained models
- **No Usage Limits**: Unlimited queries on your hardware

**Access Level**: Premium only (`premium`)

---

## AI Analytics Module (`ai_analytics`)

**Description**: Intelligent insights into booking patterns and guest behavior through AI-powered analytics.

### Core Features

#### 1. **AI-Powered Order Analytics**
- **Booking Pattern Analysis**: AI identifies trends in booking behavior
- **Guest Behavior Insights**: Understanding customer preferences and patterns
- **Revenue Optimization**: AI suggestions for pricing and availability
- **Predictive Analytics**: Forecasting future booking trends

#### 2. **AI Calendar Analytics**
- **Availability Optimization**: AI-powered suggestions for calendar management
- **Seasonal Trend Analysis**: Understanding booking patterns across seasons
- **Capacity Planning**: Intelligent recommendations for resource allocation
- **Dynamic Pricing Insights**: AI-driven pricing optimization suggestions

#### 3. **AI Event Import**
- **URL-Based Import**: Extract event details from any website URL using AI
- **Smart Data Extraction**: Automatically identify event titles, dates, descriptions
- **Multi-Source Support**: Import from various event platforms
- **Content Enhancement**: AI-powered content improvement and standardization

#### 4. **Intelligent Query Processing**
- **Natural Language Queries**: Ask questions about your booking data in plain English
- **Smart Responses**: AI provides contextual answers about bookings and trends
- **Data Insights**: Extract meaningful insights from complex booking data
- **Reporting Automation**: AI-generated reports and summaries

### Settings Configuration

- **Default AI Provider**: Choose primary AI service (ShareAI, OpenAI, Gemini, Ollama)
- **Order Analytics**: Enable/disable AI insights on orders page
- **Calendar Analytics**: Enable/disable AI analytics on calendar page
- **AI Event Import**: Enable/disable URL-based event import functionality

### AJAX Endpoints

- `aiohm_booking_ai_query`: Process natural language queries
- `aiohm_booking_generate_insights`: Generate AI-powered insights
- `aiohm_booking_ai_extract_event_info`: Extract event data from URLs
- `aiohm_booking_ai_import_event`: Import events using AI processing

---

## AI Provider Abstract Framework

### Base Functionality
All AI providers extend `AIOHM_BOOKING_AI_Provider_Module_Abstract` which provides:

- **Standardized API**: Consistent interface across all AI providers
- **Error Handling**: Unified error management and reporting
- **Connection Testing**: Built-in API validation capabilities
- **Settings Management**: Standardized configuration handling
- **Security**: Secure API key storage and transmission

### Common Features Across Providers

1. **API Key Management**: Secure storage and configuration
2. **Connection Testing**: Validate API credentials and connectivity
3. **Error Handling**: Comprehensive error reporting and logging
4. **Rate Limiting**: Intelligent request management
5. **Caching**: Response caching for improved performance
6. **Logging**: Detailed activity logging for debugging

---

## Integration Points

### Frontend Integration
- **Booking Forms**: AI-enhanced form validation and suggestions
- **Search Enhancement**: Intelligent search with natural language processing
- **Customer Support**: AI-powered chatbot capabilities
- **Content Suggestions**: Dynamic content recommendations

### Admin Integration
- **Dashboard Analytics**: AI insights prominently displayed
- **Order Management**: Intelligent order processing and recommendations
- **Calendar Management**: AI-enhanced availability optimization
- **Reporting**: Automated AI-generated reports

### Developer Integration
- **Hooks and Filters**: Extensive customization options
- **REST API Access**: Full AI features available via RESTful API
- **API Endpoints**: Dedicated endpoints for AI analytics, insights, and queries
- **Custom Providers**: Framework for adding new AI providers
- **Event System**: Comprehensive event-driven architecture
- **SDK Support**: Official SDKs for seamless AI feature integration

#### AI-Specific API Endpoints
- `GET /api/analytics/ai-insights` - Retrieve AI-generated insights
- `POST /api/ai/query` - Submit natural language queries
- `POST /api/ai/extract-event` - AI-powered event data extraction
- `GET /api/ai/providers` - List available AI providers and status

---

## Requirements

### System Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **License**: AIOHM Booking Pro license (Premium tier)
- **Internet Connection**: Required for cloud-based AI providers

### AI Provider Requirements
- **OpenAI**: Valid OpenAI API key with credits
- **Gemini**: Google AI Studio API key
- **ShareAI**: ShareAI account and API access
- **Ollama**: Self-hosted Ollama installation

---

## Security Considerations

### Data Privacy
- **API Key Encryption**: All API keys stored securely
- **Data Transmission**: Secure HTTPS communication
- **Local Processing**: Ollama option for complete privacy
- **Minimal Data Sharing**: Only necessary data sent to AI providers

### Access Control
- **Premium Verification**: Automatic license validation
- **User Capabilities**: WordPress capability-based access control
- **Nonce Protection**: CSRF protection for all AJAX requests
- **Input Sanitization**: Comprehensive data validation

---

## Usage Examples

### Basic AI Query
```javascript
// Frontend AI query example
jQuery.post(ajaxurl, {
    action: 'aiohm_booking_ai_query',
    query: 'Show me booking trends for this month',
    nonce: aiohm_booking_nonce
}, function(response) {
    if (response.success) {
        console.log(response.data.answer);
    }
});
```

### Event Import from URL
```javascript
// AI-powered event import
jQuery.post(ajaxurl, {
    action: 'aiohm_booking_ai_extract_event_info',
    url: 'https://example.com/event-page',
    nonce: aiohm_booking_nonce
}, function(response) {
    if (response.success) {
        // Populate form with extracted data
        populateEventForm(response.data);
    }
});
```

---

## Support and Documentation

### Getting Started
1. Activate AIOHM Booking Pro license
2. Configure AI provider credentials in Settings
3. Test connections using built-in validation
4. Enable desired AI features in AI Analytics settings
5. Start using AI-enhanced booking features

### Troubleshooting
- **Connection Issues**: Use built-in connection testing
- **API Errors**: Check error logs in WordPress admin
- **Rate Limits**: Monitor usage in provider dashboards
- **Performance**: Enable caching for improved response times

### Best Practices
- **API Key Security**: Store keys securely, rotate regularly
- **Usage Monitoring**: Track API usage to manage costs
- **Testing**: Use test mode during development
- **Backup**: Regular backups before major configuration changes

---

## Conclusion

AIOHM Booking Pro's AI features provide a comprehensive suite of intelligent tools that enhance every aspect of the booking experience. From automated analytics and smart recommendations to natural language processing and predictive insights, these features help booking businesses operate more efficiently and provide better customer experiences.

The modular architecture allows for easy integration of new AI providers and custom functionality, ensuring the system can evolve with advancing AI technology while maintaining security and performance standards.