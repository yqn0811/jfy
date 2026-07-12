<?php

namespace app\command;

use app\common\service\user\UserService;
use app\common\model\user\WdXcxUser;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class TestPosterCommand extends Command
{
    protected function configure()
    {
        $this->setName('test:poster')
            ->setDescription('Test Poster Generation');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("Starting poster generation test...");
        
        if (!defined('ROOT_HOST')) {
            define('ROOT_HOST', 'http://localhost');
        }
        
        try {
            $user = WdXcxUser::where('is_show_home', 1)->order('id', 'desc')->find();
            if (!$user) {
                $output->writeln("No user with is_show_home=1 found. Trying any user...");
                $user = WdXcxUser::order('id', 'desc')->find();
            }
            
            if (!$user) {
                $output->writeln("No users found in database.");
                return;
            }
            
            $userId = $user->id;
            $output->writeln("Testing with User ID: " . $userId);
            
            // Assuming we are in the root of the app, app() helper should work if framework is loaded
            $app = app();
            $userService = new UserService($app);
            
            $result = $userService->getHomeSharePoster($userId);
            
            $output->writeln("Result:");
            $output->writeln(print_r($result, true));
            
        } catch (\Exception $e) {
            $output->writeln("Error: " . $e->getMessage());
            $output->writeln($e->getTraceAsString());
        }
    }
}
