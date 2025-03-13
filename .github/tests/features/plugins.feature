Feature: Manage WordPress plugins

  Background:
    Given I log in as an admin

  @upstreamonly
  Scenario: Install, activate, deactivate, and delete a plugin
    When I go to "/wp-admin/plugin-install.php?tab=search&s=hello+dolly"
    And I follow "Hello Dolly"
    Then print current URL
    Then I should see "Hello Dolly" in the "#plugin-information-title" element

    When I follow "Install Now"
    Then print current URL
    And I should see "Successfully installed the plugin Hello Dolly"
