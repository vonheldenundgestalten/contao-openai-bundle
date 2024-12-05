# Contao OpenAI Bundle

The purpose of this extension is to quickly and easily generate meta descriptions and titles from page content using ChatGPT (OpenAI). Page content also includes dynamic pages generated through the Contao News extension.

In the screenshot below you can see some settings to get you started with relatively good results.

## Getting started

Install by hand / command line with
```
composer require vonheldenundgestalten/contao-openai-bundle
```
or through the Contao Manager interface.

Add this to the .htaccess of a project
```
RewriteCond %{REQUEST_URI} !^/_gpt*
```

## Compability

| Contao Version | PHP Version |
|----------------|-------------|
| \>= 4.9        | ^7.0 ǀǀ ^8.0 |


## Important note

- An OpenAI developer account is required. Sign up [here](https://platform.openai.com/signup). 
- The required token is also created [there](https://platform.openai.com/account/api-keys).
- There is a fee to use the OpenAI API. An overview of OpenAI pricing can be found here: [https://openai.com/pricing](https://openai.com/pricing)
- We tested a lot and so far we haven't gotten more than $5 a month

## TinyMCE Plugin notes
![](docs/tinymce.png)

Please make sure you don't have a custom be_tinyMCE.html5 template. If so, take a look at src/Resources/contao/templates/be_tinyMCE.html5 and adjust the relevant places manually.

## Screenshots

![](docs/preview.png)

## Best practise

![](docs/settings.png)

- define usage limit in OpenAPI API Backend to have control over costs
- english versions of the prompts would be:

For the title:
> Write a concise page title consisting of 5 to 6 words for the following text:
>
For the description:
> Write an informative/emphatic/appealing page description for the following text that contains less than 160 characters including spaces:
>

## How to use

- [ ] Insert token
- [ ] Choose GPT model
- [ ] Insert preferred Meta-title prompt (e.g. Schreibe für folgenden Text einen prägnanten Seitentitel bestehend aus 5 bis 6 Wörtern:)
- [ ] Insert preferred Meta-description prompt (e.g. Schreibe für folgenden Text eine informative/emphatische/ansprechende Seitenbeschreibung, die weniger als 160 Zeichen inklusive Leerzeichen enthält:)
- [ ] Set temperature (recommended: 0.5)
- [ ] Set max_tokens (recommended: 300)
- [ ] Set optional settings like hidden elements and custom fields
- [ ] optional: add "tl_news" to the allowed tables to active the buttons for the News
- [ ] Go to page settings and use the buttons below SERP preview
- [ ] Enjoy the magic :)

## To-Do

- [ ] Integrate token calculator (e.g. [GPT-3-Encoder-PHP](https://github.com/CodeRevolutionPlugins/GPT-3-Encoder-PHP))
- [ ] Content weighting through ChatGPT as pre-fetch event
- [ ] Define personality profile (role) for Chat completions API model
- [ ] Considerations and testing for the actual maximum character length for the request
- [ ] Considerations on how serialized content will be handled in the future
- [ ] Make costs per Request more transparent (show used tokens and calculate with OpenAI pricing)
- [ ] do
- [ ] some
- [ ] [magic🪄](https://media.tenor.com/IOEsG9ldvhAAAAAd/mr-bean.gif)

## new Features
- v0.2.0 -> TinyMCE AI-Text generation Dialog
- v1.0.0 -> add Contao 5 compatibility 
- v1.1.0 -> Contao Backend Help Bot powered by CustomGPT

## Support
Contao OpenAI Bundle is a project for the community. Please consider giving feedback or creating pull requests to support the ongoing development.
