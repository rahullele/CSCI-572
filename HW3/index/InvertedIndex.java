import java.io.IOException;
import java.util.StringTokenizer;

import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.IntWritable;
import org.apache.hadoop.io.LongWritable;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;
import org.apache.hadoop.mapreduce.lib.input.FileSplit;
import java.util.*;

public class InvertedIndex {
    
public static class WordCountMapper
       extends Mapper<LongWritable, Text, Text, Text>{

    private final static IntWritable one = new IntWritable(1);
    private Text word = new Text();

    public void map(LongWritable key, Text value, Context context
                    ) throws IOException, InterruptedException {
        
    String fp = ((FileSplit) context.getInputSplit()).getPath().getName();

      String line=value.toString();
 

 
String words[]=line.split(" ");
 
for(String str:words){
 

 
context.write(new Text(str), new Text(fp.replace(".txt","")));
      }
    }
  }

public static class WordCountReducer
       extends Reducer<Text,Text,Text,Text> {
    

    public void reduce(Text key, Iterable<Text> values,
                       Context context
                       ) throws IOException, InterruptedException {
HashMap<String,Integer> map=new HashMap<String,Integer>();
 
int cnt=0;
 
for(Text t:values){
 
String a=t.toString();
 

 
if(map!=null &&map.get(a)!=null){
 
cnt=(int)map.get(a);
 
map.put(a, ++cnt);
 
}else{
  
map.put(a, 1);
 
}    
 
}
 

 String s="";
for(String k:map.keySet()){
		   String keys=k.toString();
		   String value=map.get(k).toString();
		   s=s+keys+":"+value+"	";
		}
context.write(key, new Text(s));
    }
  }
    
public static void main(String[] args) throws Exception{
    Configuration conf = new Configuration();
    Job job = new Job();
    job.setJarByClass(InvertedIndex.class);
    job.setJobName("Inverted Index");
   
   FileInputFormat.addInputPath(job, new Path(args[0]));
    FileOutputFormat.setOutputPath(job, new Path(args[1]));
    job.setMapperClass(WordCountMapper.class);
    job.setReducerClass(WordCountReducer.class);
    
    job.setOutputKeyClass(Text.class);
    job.setOutputValueClass(Text.class);
    
    job.waitForCompletion(true);
}
 
}
