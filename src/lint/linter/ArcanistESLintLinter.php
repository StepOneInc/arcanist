<?php

final class ArcanistESLintLinter extends ArcanistExternalLinter {

    private $eslintenv;
    private $eslintconfig;

    public function getInfoName() {
        return 'ESLint';
    }

    public function getInfoURI() {
        return 'https://www.eslint.org';
    }

    public function getInfoDescription() {
        return pht('ESLint is a linter for JavaScript source files.');
    }

    public function getVersion() {
        $output = exec('eslint --version');

        if (strpos($output, 'command not found') !== false) {
            return false;
        }

        return $output;
    }

    public function getLinterName() {
        return 'ESLINT';
    }

    public function getLinterConfigurationName() {
        return 'eslint';
    }

    public function getDefaultBinary() {
        return 'eslint';
    }

    public function getInstallInstructions() {
        return pht('Install ESLint using `%s`.', 'npm install -g eslint');
    }

    public function getMandatoryFlags() {
        $options = array();

        $options[] = '--format=jslint-xml';

        if ($this->eslintenv) {
            $options[] = '--env='.$this->eslintenv;
        }

        if ($this->eslintconfig) {
            $options[] = '--config='.$this->eslintconfig;
        }

        return $options;
    }

    public function getLinterConfigurationOptions() {
        $options = array(
            'eslint.eslintenv' => array(
                'type' => 'optional string',
                'help' => pht('enables specific environments.'),
            ),
            'eslint.eslintconfig' => array(
                'type' => 'optional string',
                'help' => pht('config file to use the default is .eslint.'),
            ),
        );
        return $options + parent::getLinterConfigurationOptions();
    }

    public function setLinterConfigurationValue($key, $value) {

        switch ($key) {
            case 'eslint.eslintenv':
                $this->eslintenv = $value;
                return;
            case 'eslint.eslintconfig':
                $this->eslintconfig = $value;
                return;
        }

        return parent::setLinterConfigurationValue($key, $value);
    }

    protected function parseLinterOutput($path, $err, $stdout, $stderr) {
        $xml_result = new SimpleXMLElement($stdout);
        $errors = array();
        $messages = array();
        foreach ($xml_result->file->issue as $issue) {
            $message = new ArcanistLintMessage();
            $message->setPath($path);
            $message->setLine($issue['line']);
            $message->setChar($issue['char']);
            $message->setName($issue['reason']);
            $message->setDescription("Evidence: " . $issue['evidence']);
            $message->setSeverity(ArcanistLintSeverity::SEVERITY_ERROR);
            $message->setCode(ArcanistLintSeverity::SEVERITY_ERROR);
            $messages[] = $message;
        }
        foreach ($errors as $err) {
            $this->raiseLintAtLine(
                $err['line'],
                $err['col'],
                self::ESLINT_ERROR,
                $err['reason']);
        }

        return $messages;
    }

}